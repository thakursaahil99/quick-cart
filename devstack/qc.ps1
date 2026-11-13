#Requires -Version 5.1
<#
    QuickCart developer CLI (native Windows stack).
    Run through devstack\qc.cmd, e.g.:  devstack\qc start
#>
. (Join-Path $PSScriptRoot 'lib\QuickCart.DevStack.ps1')
. (Join-Path $PSScriptRoot 'lib\QuickCart.Magento.ps1')
. (Join-Path $PSScriptRoot 'lib\QuickCart.Share.ps1')

$command = 'help'
$rest = @()
if ($args.Count -ge 1) { $command = [string]$args[0] }
if ($args.Count -ge 2) { $rest = [string[]]$args[1..($args.Count - 1)] }

function Show-QcHelp {
    @'
QuickCart dev stack - usage: devstack\qc <command> [args]

  install-stack      Download + configure PHP, Composer, MySQL, OpenSearch, Nginx
  install-magento    composer install + bin/magento setup:install (idempotent)
  start | stop | restart | status
  share | unshare    Publish the storefront on a public https URL (Cloudflare tunnel);
                     admin stays local-only. unshare restores the local URLs
  clean-static       Rebuild theme CSS/JS after editing app/design (clears pub/static,
                     var/view_preprocessed, bumps the static version, flushes cache)
  magento <args>     Run bin/magento         e.g. qc magento cache:flush
  composer <args>    Run Composer in src/    e.g. qc composer show magento/*
  php <args>         Run the stack's PHP
  mysql [args]       MySQL client on the Magento database (as root)
'@ | Write-Host
}

$code = 0
try {
    switch ($command) {
        'install-stack'   { Install-QcStack }
        'install-magento' { Install-QcMagento }
        'start'           { Start-QcStack; Show-QcStatus }
        'stop'            { Stop-QcStack }
        'restart'         { Stop-QcStack; Start-QcStack; Show-QcStatus }
        'status'          { Show-QcStatus; Show-QcShareStatus }
        'share'           { Start-QcShare }
        'unshare'         { Stop-QcShare }
        'clean-static' {
            $cfg = Get-QcConfig
            foreach ($rel in @('pub\static\frontend', 'pub\static\adminhtml', 'var\view_preprocessed', 'pub\static\deployed_version.txt')) {
                $path = Join-Path $cfg.MAGENTO_ROOT $rel
                if (Test-Path -LiteralPath $path) { Remove-Item -LiteralPath $path -Recurse -Force }
            }
            Invoke-QcMagento $cfg @('cache:flush')
            Write-QcOk 'Static files cleared; new static version URLs will be generated on the next request'
        }
        'magento'         { Invoke-QcMagento (Get-QcConfig) $rest; $code = $LASTEXITCODE }
        'php'             { Invoke-QcPhp (Get-QcConfig) $rest; $code = $LASTEXITCODE }
        'composer' {
            $cfg = Get-QcConfig
            Invoke-QcComposer $cfg (@("--working-dir=$($cfg.MAGENTO_ROOT)") + $rest)
            $code = $LASTEXITCODE
        }
        'mysql' {
            $cfg = Get-QcConfig
            & (Join-Path $cfg.MYSQL_DIR 'bin\mysql.exe') "--defaults-extra-file=$($cfg.MYSQL_ROOT_CNF)" $cfg.DB_NAME @rest
            $code = $LASTEXITCODE
        }
        default { Show-QcHelp }
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    $code = 1
}
exit $code
