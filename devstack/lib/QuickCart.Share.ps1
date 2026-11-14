#Requires -Version 5.1
<#
    QuickCart - share the local storefront on a public https URL (Cloudflare quick tunnel).

    qc share    starts the tunnel, points Magento's storefront URLs at it and moves the
                admin to a local-only URL (nginx also refuses admin/setup paths that
                arrive through Cloudflare - see templates/nginx.conf.tpl)
    qc unshare  stops the tunnel and restores the local URLs

    The public URL works only while this PC, the stack and the tunnel are running.
#>

function Get-QcSharePaths($cfg) {
    return [pscustomobject]@{
        State = Join-Path $cfg.RUN_DIR 'share.json'
        Log   = Join-Path $cfg.LOG_DIR 'cloudflared.log'
    }
}

function Get-QcCloudflared {
    $command = Get-Command cloudflared -ErrorAction SilentlyContinue
    if ($command) { return $command.Source }
    foreach ($candidate in @("${env:ProgramFiles(x86)}\cloudflared\cloudflared.exe", "$env:ProgramFiles\cloudflared\cloudflared.exe")) {
        if (Test-Path -LiteralPath $candidate) { return $candidate }
    }
    throw 'cloudflared is not installed (winget install --id Cloudflare.cloudflared)'
}

function Update-QcNginxConfig($cfg) {
    Install-QcNginx $cfg
    if (@(Get-QcProcess 'nginx.exe' $cfg.NGINX_DIR).Count -eq 0) { return }
    $nginx = Join-Path $cfg.NGINX_DIR 'nginx.exe'
    $prefix = (ConvertTo-QcForwardSlash $cfg.NGINX_DIR) + '/'
    Push-Location $cfg.NGINX_DIR
    try {
        & $nginx -p $prefix -t -q
        Assert-QcExit 'nginx -t'
        & $nginx -p $prefix -s reload
        Assert-QcExit 'nginx reload'
    } finally {
        Pop-Location
    }
    Write-QcOk 'Nginx reloaded'
}

function Get-QcMagentoConfigValue($cfg, [string]$Path) {
    $value = (Invoke-QcMagento $cfg @('config:show', $Path) | Out-String).Trim()
    if ($LASTEXITCODE -ne 0) { return '' }
    return $value
}

function Set-QcMagentoConfig($cfg, [System.Collections.Specialized.OrderedDictionary]$Values) {
    foreach ($path in $Values.Keys) {
        Invoke-QcMagento $cfg @('config:set', '--no-interaction', $path, [string]$Values[$path]) | Out-Null
        Assert-QcExit "config:set $path"
    }
}

function Stop-QcTunnelProcess($cfg) {
    $paths = Get-QcSharePaths $cfg
    Stop-QcProcessList (Get-QcProcess 'cloudflared.exe' $paths.Log)
}

function Start-QcShare {
    $cfg = Get-QcConfig
    $paths = Get-QcSharePaths $cfg
    if (-not (Test-QcPort $cfg.HTTP_PORT)) { throw 'The stack is not running - run: devstack\qc start' }
    $cloudflared = Get-QcCloudflared

    Write-QcStep 'Preparing Nginx (admin and setup are refused through the tunnel)'
    Update-QcNginxConfig $cfg

    $url = $null
    $tunnelRunning = @(Get-QcProcess 'cloudflared.exe' $paths.Log).Count -gt 0
    if ($tunnelRunning -and (Test-Path -LiteralPath $paths.State)) {
        # Keep the existing public URL (quick tunnels get a new URL on every start).
        $url = (Get-Content -LiteralPath $paths.State -Raw | ConvertFrom-Json).url
        Write-QcStep 'Reusing the running Cloudflare tunnel'
    } else {
        Write-QcStep 'Starting Cloudflare quick tunnel'
        Stop-QcTunnelProcess $cfg
        if (Test-Path -LiteralPath $paths.Log) { Remove-Item -LiteralPath $paths.Log -Force }
        Start-Process -FilePath $cloudflared -WindowStyle Hidden -ArgumentList @(
            'tunnel', '--no-autoupdate', '--url', "http://127.0.0.1:$($cfg.HTTP_PORT)", '--logfile', $paths.Log
        )
    }
    $deadline = (Get-Date).AddSeconds(90)
    while (-not $url -and (Get-Date) -lt $deadline) {
        Start-Sleep -Seconds 1
        if (Test-Path -LiteralPath $paths.Log) {
            $match = Select-String -LiteralPath $paths.Log -Pattern 'https://[a-z0-9-]+\.trycloudflare\.com' | Select-Object -Last 1
            if ($match) { $url = $match.Matches[0].Value }
        }
    }
    if (-not $url) { throw "Tunnel did not report a URL within 90s (see $($paths.Log))" }
    Write-QcOk "Tunnel: $url"

    if (-not (Test-Path -LiteralPath $paths.State)) {
        $previous = [ordered]@{
            unsecure = Get-QcMagentoConfigValue $cfg 'web/unsecure/base_url'
            secure   = Get-QcMagentoConfigValue $cfg 'web/secure/base_url'
        }
        if (-not $previous.unsecure) { $previous.unsecure = $cfg.BASE_URL }
        if (-not $previous.secure) { $previous.secure = $cfg.BASE_URL }
    } else {
        $previous = Get-Content -LiteralPath $paths.State -Raw | ConvertFrom-Json | Select-Object -ExpandProperty previous
    }
    [ordered]@{ url = $url; started = (Get-Date).ToString('s'); previous = $previous } |
        ConvertTo-Json | Set-Content -LiteralPath $paths.State -Encoding ASCII

    # The unsecure base URL stays http:// on purpose: Magento forces the admin onto the
    # secure (public) URL whenever the unsecure base URL is https (AdminPathConfig), and the
    # admin must stay on this PC. Public visits arrive over https, so with
    # use_in_frontend=1 every storefront link Magento renders is https.
    Write-QcStep 'Pointing the storefront at the public URL'
    Set-QcMagentoConfig $cfg ([ordered]@{
        'admin/url/custom'             = $cfg.BASE_URL
        'admin/url/use_custom'         = '1'
        'web/unsecure/base_url'        = ($url -replace '^https://', 'http://') + '/'
        'web/secure/base_url'          = "$url/"
        'web/secure/use_in_frontend'   = '1'
        'web/secure/use_in_adminhtml'  = '0'
    })
    # Admin links are generated from the admin store's (store_id 0) base URLs. The admin
    # "Use Custom Admin URL" save handler writes these; config:set does not, so set them
    # here - otherwise every admin redirect points at the public URL (refused by nginx).
    foreach ($path in @('web/unsecure/base_url', 'web/secure/base_url')) {
        Invoke-QcMagento $cfg @('config:set', '--no-interaction', '--scope=stores', '--scope-code=admin', $path, $cfg.BASE_URL) | Out-Null
        Assert-QcExit "config:set (admin store) $path"
    }
    Invoke-QcMagento $cfg @('cache:flush') | Out-Null

    Write-QcStep 'Waiting for the public URL to answer'
    $code = ''
    $deadline = (Get-Date).AddSeconds(120)
    while ((Get-Date) -lt $deadline) {
        $code = "$(& curl.exe -s -o NUL -w '%{http_code}' --max-time 60 "$url/")"
        if ($code -eq '200') { break }
        Start-Sleep -Seconds 3
    }
    if ($code -ne '200') { Write-QcWarn "Public URL answered HTTP $code - it may need another minute" }

    Write-QcStep 'QuickCart is live'
    Write-QcInfo "Public storefront : $url/"
    Write-QcInfo "Admin (this PC)   : $($cfg.BASE_URL)$($cfg.ADMIN_FRONTNAME)"
    Write-QcInfo 'Keep this PC, the stack and the tunnel running. Stop with: devstack\qc unshare'
}

function Stop-QcShare {
    $cfg = Get-QcConfig
    $paths = Get-QcSharePaths $cfg
    Write-QcStep 'Stopping public share'
    Stop-QcTunnelProcess $cfg

    $unsecure = $cfg.BASE_URL
    $secure = $cfg.BASE_URL
    if (Test-Path -LiteralPath $paths.State) {
        $state = Get-Content -LiteralPath $paths.State -Raw | ConvertFrom-Json
        if ($state.previous.unsecure) { $unsecure = $state.previous.unsecure }
        if ($state.previous.secure) { $secure = $state.previous.secure }
    }
    Set-QcMagentoConfig $cfg ([ordered]@{
        'web/unsecure/base_url'      = $unsecure
        'web/secure/base_url'        = $secure
        'web/secure/use_in_frontend' = '0'
        'admin/url/use_custom'       = '0'
    })
    Invoke-QcMagento $cfg @('cache:flush') | Out-Null
    if (Test-Path -LiteralPath $paths.State) { Remove-Item -LiteralPath $paths.State -Force }
    Write-QcOk "Storefront back on $unsecure"
}

function Show-QcShareStatus {
    $cfg = Get-QcConfig
    $paths = Get-QcSharePaths $cfg
    if (-not (Test-Path -LiteralPath $paths.State)) { return }
    $state = Get-Content -LiteralPath $paths.State -Raw | ConvertFrom-Json
    $running = @(Get-QcProcess 'cloudflared.exe' $paths.Log).Count -gt 0
    $label = 'stopped - run qc share again'
    if ($running) { $label = 'running' }
    Write-QcInfo "Public share  $($state.url)/  (tunnel $label)"
}
