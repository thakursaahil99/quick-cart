#Requires -Version 5.1
<#
    QuickCart - native Windows development stack (no Docker).

    Portable PHP + Composer + MySQL 8.4 + OpenSearch 3 + Nginx installed under
    STACK_DIR (default E:/quickcart-stack). Nothing is installed system-wide, no
    Windows services are registered and no admin rights are needed.

    This file is dot-sourced by devstack/qc.ps1; use `devstack\qc help`.
#>
Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$script:QcDevStackDir = (Get-Item (Split-Path -Parent $PSScriptRoot)).FullName
$script:QcRepoRoot    = (Get-Item (Split-Path -Parent $script:QcDevStackDir)).FullName
$script:QcMagentoRoot = Join-Path $script:QcRepoRoot 'src'
$script:QcEnvFile     = Join-Path $script:QcDevStackDir '.env'
$script:QcEnvExample  = Join-Path $script:QcDevStackDir '.env.example'

# Extensions required by Magento 2.4.9 (magento/framework + product-community-edition).
$script:QcRequiredPhpModules = @(
    'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'ftp', 'gd', 'hash', 'iconv', 'intl',
    'json', 'libxml', 'mbstring', 'openssl', 'pcre', 'pdo_mysql', 'simplexml', 'soap', 'sockets',
    'sodium', 'tokenizer', 'xmlwriter', 'xsl', 'zip', 'zlib', 'Zend OPcache'
)
# Shared (DLL) extensions to enable in php.ini; the rest are compiled into the Windows build.
$script:QcPhpDllExtensions = @(
    'bcmath', 'curl', 'fileinfo', 'ftp', 'gd', 'intl', 'mbstring', 'openssl', 'pdo_mysql', 'soap',
    'sockets', 'sodium', 'xsl', 'zip'
)

# ----------------------------------------------------------------------------- output

function Write-QcStep([string]$Message) { Write-Host "==> $Message" -ForegroundColor Cyan }
function Write-QcOk([string]$Message)   { Write-Host "    [ok] $Message" -ForegroundColor Green }
function Write-QcInfo([string]$Message) { Write-Host "    $Message" }
function Write-QcWarn([string]$Message) { Write-Host "    [warn] $Message" -ForegroundColor Yellow }

function Assert-QcExit([string]$What) {
    if ($LASTEXITCODE -ne 0) { throw "$What failed (exit code $LASTEXITCODE)" }
}

# ----------------------------------------------------------------------------- configuration

function Read-QcEnvFile([string]$Path) {
    $values = [ordered]@{}
    if (-not (Test-Path -LiteralPath $Path)) { return $values }
    foreach ($line in @(Get-Content -LiteralPath $Path -Encoding UTF8)) {
        $trimmed = $line.Trim()
        if ($trimmed -eq '' -or $trimmed.StartsWith('#')) { continue }
        $idx = $trimmed.IndexOf('=')
        if ($idx -lt 1) { continue }
        $key = $trimmed.Substring(0, $idx).Trim()
        $val = $trimmed.Substring($idx + 1).Trim()
        if ($val.Length -ge 2 -and (($val[0] -eq '"' -and $val[-1] -eq '"') -or ($val[0] -eq "'" -and $val[-1] -eq "'"))) {
            $val = $val.Substring(1, $val.Length - 2)
        }
        $values[$key] = $val
    }
    return $values
}

function New-QcSecret([int]$Length = 20) {
    # Alphanumeric (safe in ini/cnf/CLI) and always mixing letters + digits (Magento admin rule).
    $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $bytes = New-Object byte[] $Length
    do {
        $rng.GetBytes($bytes)
        $secret = -join ($bytes | ForEach-Object { $chars[$_ % $chars.Length] })
    } until ($secret -cmatch '[a-z]' -and $secret -cmatch '[A-Z]' -and $secret -match '\d')
    return $secret
}

function Initialize-QcEnvFile {
    if (-not (Test-Path -LiteralPath $script:QcEnvFile)) {
        Copy-Item -LiteralPath $script:QcEnvExample -Destination $script:QcEnvFile
        Write-QcOk 'Created devstack/.env from .env.example'
    }
    $lines = @(Get-Content -LiteralPath $script:QcEnvFile -Encoding UTF8)
    $generated = @()
    foreach ($key in @('DB_PASSWORD', 'DB_ROOT_PASSWORD', 'ADMIN_PASSWORD')) {
        for ($i = 0; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match "^\s*$key\s*=\s*$") {
                $lines[$i] = "$key=" + (New-QcSecret 20)
                $generated += $key
            }
        }
    }
    if ($generated.Count -gt 0) {
        [IO.File]::WriteAllLines($script:QcEnvFile, [string[]]$lines, (New-Object Text.UTF8Encoding $false))
        Write-QcOk ("Generated local secrets in devstack/.env: " + ($generated -join ', '))
    }
}

function Get-QcConfig {
    $cfg = [ordered]@{}
    $defaults = Read-QcEnvFile $script:QcEnvExample
    $local = Read-QcEnvFile $script:QcEnvFile
    foreach ($k in $defaults.Keys) { $cfg[$k] = $defaults[$k] }
    foreach ($k in $local.Keys) { $cfg[$k] = $local[$k] }

    $stack = [IO.Path]::GetFullPath(($cfg['STACK_DIR'] -replace '/', '\'))
    if ($stack -match '\s') { throw "STACK_DIR must not contain spaces (OpenSearch/MySQL scripts break): $stack" }
    $cfg['STACK_DIR']           = $stack
    $cfg['DOWNLOAD_DIR']        = Join-Path $stack 'downloads'
    $cfg['PHP_DIR']             = Join-Path $stack 'php'
    $cfg['PHP_EXE']             = Join-Path $stack 'php\php.exe'
    $cfg['PHP_CGI_EXE']         = Join-Path $stack 'php\php-cgi.exe'
    $cfg['COMPOSER_DIR']        = Join-Path $stack 'composer'
    $cfg['COMPOSER_PHAR']       = Join-Path $stack 'composer\composer.phar'
    $cfg['MYSQL_DIR']           = Join-Path $stack 'mysql'
    $cfg['MYSQL_DATA_DIR']      = Join-Path $stack 'data\mysql'
    $cfg['OPENSEARCH_DIR']      = Join-Path $stack 'opensearch'
    $cfg['OPENSEARCH_DATA_DIR'] = Join-Path $stack 'data\opensearch'
    $cfg['NGINX_DIR']           = Join-Path $stack 'nginx'
    $cfg['CONF_DIR']            = Join-Path $stack 'conf'
    $cfg['MYSQL_INI']           = Join-Path $stack 'conf\my.ini'
    $cfg['MYSQL_ROOT_CNF']      = Join-Path $stack 'conf\mysql-root.cnf'
    $cfg['CACERT']              = Join-Path $stack 'conf\cacert.pem'
    $cfg['LOG_DIR']             = Join-Path $stack 'logs'
    $cfg['TMP_DIR']             = Join-Path $stack 'tmp'
    $cfg['RUN_DIR']             = Join-Path $stack 'run'
    $cfg['REPO_ROOT']           = $script:QcRepoRoot
    $cfg['DEVSTACK_DIR']        = $script:QcDevStackDir
    $cfg['MAGENTO_ROOT']        = $script:QcMagentoRoot
    $cfg['PHP_CGI_PORT_LIST']   = @($cfg['PHP_CGI_PORTS'] -split ',' | ForEach-Object { [int]$_.Trim() })
    return [pscustomobject]$cfg
}

function ConvertTo-QcForwardSlash([string]$Path) { return $Path -replace '\\', '/' }

# ----------------------------------------------------------------------------- files

function Test-QcFileHash([string]$File, [string]$Algorithm, [string]$Expected) {
    if ([string]::IsNullOrWhiteSpace($Expected)) { return $true }
    $actual = (Get-FileHash -LiteralPath $File -Algorithm $Algorithm).Hash
    return $actual -eq $Expected.Trim().ToUpperInvariant()
}

function Get-QcRemoteChecksum([string]$Url) {
    $text = (& curl.exe -fsSL $Url | Out-String).Trim()
    Assert-QcExit "Downloading checksum $Url"
    return ($text -split '\s+')[0]
}

function Get-QcDownload {
    param(
        [Parameter(Mandatory = $true)][string[]]$Urls,
        [Parameter(Mandatory = $true)][string]$OutFile,
        [string]$Algorithm = 'SHA256',
        [string]$Checksum = ''
    )
    $name = Split-Path -Leaf $OutFile
    if (Test-Path -LiteralPath $OutFile) {
        if (Test-QcFileHash $OutFile $Algorithm $Checksum) { Write-QcOk "Using cached $name"; return }
        Write-QcWarn "Cached $name failed $Algorithm verification - downloading again"
        Remove-Item -LiteralPath $OutFile -Force
    }
    $partial = "$OutFile.part"
    foreach ($url in $Urls) {
        Write-QcInfo "Downloading $url"
        & curl.exe -fL --retry 3 --retry-delay 5 --connect-timeout 30 -C - -o $partial $url
        if ($LASTEXITCODE -eq 0) {
            Move-Item -LiteralPath $partial -Destination $OutFile -Force
            if (-not (Test-QcFileHash $OutFile $Algorithm $Checksum)) {
                Remove-Item -LiteralPath $OutFile -Force
                throw "$name failed $Algorithm verification (expected $Checksum)"
            }
            if ($Checksum) { Write-QcOk "$name downloaded and $Algorithm verified" } else { Write-QcOk "$name downloaded" }
            return
        }
        if (Test-Path -LiteralPath $partial) { Remove-Item -LiteralPath $partial -Force }
    }
    throw "Could not download $name from: $($Urls -join ', ')"
}

function Expand-QcArchive([string]$Archive, [string]$Destination, [string]$TmpDir) {
    # Extracts a zip with Windows' bsdtar (much faster than Expand-Archive) and
    # flattens the single top-level folder most vendor zips contain.
    $work = Join-Path $TmpDir ('x' + [guid]::NewGuid().ToString('N').Substring(0, 8))
    New-Item -ItemType Directory -Path $work -Force | Out-Null
    try {
        & tar.exe -xf $Archive -C $work
        Assert-QcExit "Extracting $(Split-Path -Leaf $Archive)"
        $children = @(Get-ChildItem -LiteralPath $work)
        $source = $work
        if ($children.Count -eq 1 -and $children[0].PSIsContainer) { $source = $children[0].FullName }
        Move-Item -LiteralPath $source -Destination $Destination
    } finally {
        if (Test-Path -LiteralPath $work) { Remove-Item -LiteralPath $work -Recurse -Force }
    }
}

function Write-QcTemplate([string]$TemplateName, [string]$OutFile, [hashtable]$Vars) {
    $template = Join-Path $script:QcDevStackDir "templates\$TemplateName"
    $text = [IO.File]::ReadAllText($template)
    foreach ($key in $Vars.Keys) { $text = $text.Replace('{{' + $key + '}}', [string]$Vars[$key]) }
    if ($text -match '\{\{[A-Z0-9_]+\}\}') { throw "Unresolved placeholder $($Matches[0]) in $TemplateName" }
    [IO.File]::WriteAllText($OutFile, $text, (New-Object Text.UTF8Encoding $false))
}

# ----------------------------------------------------------------------------- processes & ports

function Test-QcPort([int]$Port, [string]$HostName = '127.0.0.1') {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $async = $client.BeginConnect($HostName, $Port, $null, $null)
        if (-not $async.AsyncWaitHandle.WaitOne(400)) { return $false }
        $client.EndConnect($async)
        return $true
    } catch {
        return $false
    } finally {
        $client.Close()
    }
}

function Wait-QcPort([int]$Port, [int]$TimeoutSeconds, [string]$Label) {
    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    while ((Get-Date) -lt $deadline) {
        if (Test-QcPort $Port) { return }
        Start-Sleep -Milliseconds 500
    }
    throw "$Label did not start listening on port $Port within $TimeoutSeconds seconds (see logs in STACK_DIR\logs)"
}

function Get-QcProcess([string]$ProcessName, [string]$PathFragment) {
    $all = @(Get-CimInstance Win32_Process -Filter "Name = '$ProcessName'")
    return @($all | Where-Object {
        ($_.ExecutablePath -and $_.ExecutablePath.IndexOf($PathFragment, [StringComparison]::OrdinalIgnoreCase) -ge 0) -or
        ($_.CommandLine -and $_.CommandLine.IndexOf($PathFragment, [StringComparison]::OrdinalIgnoreCase) -ge 0)
    })
}

function Stop-QcProcessList($Processes) {
    foreach ($p in @($Processes)) {
        if ($null -ne $p) { Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue }
    }
}

# ----------------------------------------------------------------------------- tool wrappers

function Set-QcToolEnvironment($cfg) {
    $env:COMPOSER_HOME = Join-Path $cfg.COMPOSER_DIR 'home'
    $env:COMPOSER_CACHE_DIR = Join-Path $cfg.COMPOSER_DIR 'cache'
    $env:COMPOSER_MEMORY_LIMIT = '-1'
    $env:COMPOSER_PROCESS_TIMEOUT = '3600'
    if (($env:PATH -split ';') -notcontains $cfg.PHP_DIR) { $env:PATH = "$($cfg.PHP_DIR);$env:PATH" }
}

function Invoke-QcPhp($cfg, [string[]]$Arguments) {
    Set-QcToolEnvironment $cfg
    & $cfg.PHP_EXE @Arguments
}

function Invoke-QcComposer($cfg, [string[]]$Arguments) {
    Set-QcToolEnvironment $cfg
    & $cfg.PHP_EXE $cfg.COMPOSER_PHAR @Arguments
}

function Invoke-QcMagento($cfg, [string[]]$Arguments) {
    $magento = Join-Path $cfg.MAGENTO_ROOT 'bin\magento'
    if (-not (Test-Path -LiteralPath $magento)) { throw 'Magento is not installed yet - run: devstack\qc install-magento' }
    Set-QcToolEnvironment $cfg
    # Run from the Magento root: same drive as the code, matching php-cgi's working dir.
    Push-Location -LiteralPath $cfg.MAGENTO_ROOT
    try { & $cfg.PHP_EXE $magento @Arguments } finally { Pop-Location }
}

function Invoke-QcMysqlRoot($cfg, [string]$Sql, [string]$Database = '') {
    $mysql = Join-Path $cfg.MYSQL_DIR 'bin\mysql.exe'
    $arguments = @("--defaults-extra-file=$($cfg.MYSQL_ROOT_CNF)", '--batch', '--skip-column-names')
    if ($Database) { $arguments += $Database }
    $Sql | & $mysql @arguments
}

# ----------------------------------------------------------------------------- install: components

function Install-QcPhp($cfg) {
    Write-QcStep "PHP $($cfg.PHP_VERSION) (NTS x64)"
    if (-not (Test-Path -LiteralPath $cfg.PHP_EXE)) {
        $zip = Join-Path $cfg.DOWNLOAD_DIR "php-$($cfg.PHP_VERSION)-nts-Win32-vs17-x64.zip"
        Get-QcDownload -Urls @(
            "https://downloads.php.net/~windows/releases/archives/php-$($cfg.PHP_VERSION)-nts-Win32-vs17-x64.zip",
            "https://downloads.php.net/~windows/releases/php-$($cfg.PHP_VERSION)-nts-Win32-vs17-x64.zip"
        ) -OutFile $zip -Algorithm SHA256 -Checksum $cfg.PHP_SHA256
        Expand-QcArchive $zip $cfg.PHP_DIR $cfg.TMP_DIR
    }

    Write-QcStep 'CA certificate bundle (curl.se)'
    $caSum = Get-QcRemoteChecksum 'https://curl.se/ca/cacert.pem.sha256'
    Get-QcDownload -Urls @('https://curl.se/ca/cacert.pem') -OutFile $cfg.CACERT -Algorithm SHA256 -Checksum $caSum

    $extDir = Join-Path $cfg.PHP_DIR 'ext'
    $lines = @()
    foreach ($ext in $script:QcPhpDllExtensions) {
        if (Test-Path -LiteralPath (Join-Path $extDir "php_$ext.dll")) { $lines += "extension=$ext" }
        else { $lines += "; $ext is compiled into this PHP build" }
    }
    if (Test-Path -LiteralPath (Join-Path $extDir 'php_opcache.dll')) { $lines += 'zend_extension=opcache' }

    Write-QcTemplate 'php.ini.tpl' (Join-Path $cfg.PHP_DIR 'php.ini') @{
        PHP_VERSION     = $cfg.PHP_VERSION
        MAGENTO_VERSION = $cfg.MAGENTO_VERSION
        PHP_DIR         = ConvertTo-QcForwardSlash $cfg.PHP_DIR
        LOG_DIR         = ConvertTo-QcForwardSlash $cfg.LOG_DIR
        TMP_DIR         = ConvertTo-QcForwardSlash $cfg.TMP_DIR
        CACERT          = ConvertTo-QcForwardSlash $cfg.CACERT
        PHP_EXTENSIONS  = ($lines -join "`r`n")
    }

    # Verify every extension Magento needs is loaded (CLI and CGI share php.ini).
    $loaded = @(& $cfg.PHP_EXE -m | ForEach-Object { $_.Trim().ToLowerInvariant() })
    Assert-QcExit 'php -m'
    $missing = @($script:QcRequiredPhpModules | Where-Object { $loaded -notcontains $_.ToLowerInvariant() })
    if ($missing.Count -gt 0) { throw "PHP is missing required extensions: $($missing -join ', ')" }
    Write-QcOk ("PHP ready: " + (& $cfg.PHP_EXE -r 'echo PHP_VERSION;') + " with all Magento extensions")
}

function Install-QcComposer($cfg) {
    Write-QcStep "Composer $($cfg.COMPOSER_VERSION)"
    $sum = Get-QcRemoteChecksum "https://getcomposer.org/download/$($cfg.COMPOSER_VERSION)/composer.phar.sha256sum"
    Get-QcDownload -Urls @("https://getcomposer.org/download/$($cfg.COMPOSER_VERSION)/composer.phar") `
        -OutFile $cfg.COMPOSER_PHAR -Algorithm SHA256 -Checksum $sum
    Invoke-QcComposer $cfg @('--version', '--no-ansi')
    Assert-QcExit 'composer --version'
}

function Install-QcMysql($cfg) {
    Write-QcStep "MySQL $($cfg.MYSQL_VERSION) (portable, port $($cfg.DB_PORT))"
    if (-not (Test-Path -LiteralPath (Join-Path $cfg.MYSQL_DIR 'bin\mysqld.exe'))) {
        $series = ($cfg.MYSQL_VERSION -split '\.')[0..1] -join '.'
        $file = "mysql-$($cfg.MYSQL_VERSION)-winx64.zip"
        $zip = Join-Path $cfg.DOWNLOAD_DIR $file
        Get-QcDownload -Urls @(
            "https://cdn.mysql.com/Downloads/MySQL-$series/$file",
            "https://cdn.mysql.com/archives/mysql-$series/$file"
        ) -OutFile $zip
        Expand-QcArchive $zip $cfg.MYSQL_DIR $cfg.TMP_DIR
    }

    Write-QcTemplate 'my.ini.tpl' $cfg.MYSQL_INI @{
        MYSQL_VERSION     = $cfg.MYSQL_VERSION
        MYSQL_DIR         = ConvertTo-QcForwardSlash $cfg.MYSQL_DIR
        MYSQL_DATA_DIR    = ConvertTo-QcForwardSlash $cfg.MYSQL_DATA_DIR
        DB_PORT           = $cfg.DB_PORT
        LOG_DIR           = ConvertTo-QcForwardSlash $cfg.LOG_DIR
        MYSQL_BUFFER_POOL = $cfg.MYSQL_BUFFER_POOL
    }

    $freshDataDir = -not (Test-Path -LiteralPath (Join-Path $cfg.MYSQL_DATA_DIR 'mysql.ibd'))
    if ($freshDataDir) {
        Write-QcInfo 'Initializing data directory'
        if (Test-Path -LiteralPath $cfg.MYSQL_DATA_DIR) { Remove-Item -LiteralPath $cfg.MYSQL_DATA_DIR -Recurse -Force }
        & (Join-Path $cfg.MYSQL_DIR 'bin\mysqld.exe') "--defaults-file=$($cfg.MYSQL_INI)" '--initialize-insecure'
        Assert-QcExit 'mysqld --initialize-insecure'
        # Root has no password until provisioning below sets DB_ROOT_PASSWORD.
        Set-Content -LiteralPath $cfg.MYSQL_ROOT_CNF -Encoding ASCII -Value @(
            '[client]', 'user=root', 'host=127.0.0.1', "port=$($cfg.DB_PORT)"
        )
    }

    Start-QcMysql $cfg

    if ($freshDataDir) {
        Invoke-QcMysqlRoot $cfg "ALTER USER 'root'@'localhost' IDENTIFIED BY '$($cfg.DB_ROOT_PASSWORD)';"
        Assert-QcExit 'Setting MySQL root password'
    }
    Set-Content -LiteralPath $cfg.MYSQL_ROOT_CNF -Encoding ASCII -Value @(
        '[client]', 'user=root', "password=$($cfg.DB_ROOT_PASSWORD)", 'host=127.0.0.1', "port=$($cfg.DB_PORT)"
    )

    $db = $cfg.DB_NAME; $user = $cfg.DB_USER; $pw = $cfg.DB_PASSWORD
    $sql = @"
CREATE DATABASE IF NOT EXISTS ``$db`` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS '$user'@'127.0.0.1' IDENTIFIED BY '$pw';
CREATE USER IF NOT EXISTS '$user'@'localhost' IDENTIFIED BY '$pw';
ALTER USER '$user'@'127.0.0.1' IDENTIFIED BY '$pw';
ALTER USER '$user'@'localhost' IDENTIFIED BY '$pw';
GRANT ALL PRIVILEGES ON ``$db``.* TO '$user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON ``$db``.* TO '$user'@'localhost';
FLUSH PRIVILEGES;
"@
    Invoke-QcMysqlRoot $cfg $sql
    Assert-QcExit 'Provisioning Magento database/user'
    Write-QcOk "Database '$db' and user '$user' ready (least privilege: only $db.*)"
}

function Install-QcOpenSearch($cfg) {
    Write-QcStep "OpenSearch $($cfg.OPENSEARCH_VERSION) (port $($cfg.OPENSEARCH_PORT), heap $($cfg.OPENSEARCH_HEAP))"
    if (-not (Test-Path -LiteralPath (Join-Path $cfg.OPENSEARCH_DIR 'bin\opensearch.bat'))) {
        $v = $cfg.OPENSEARCH_VERSION
        $base = "https://artifacts.opensearch.org/releases/bundle/opensearch/$v/opensearch-$v-windows-x64.zip"
        $sum = Get-QcRemoteChecksum "$base.sha512"
        $zip = Join-Path $cfg.DOWNLOAD_DIR "opensearch-$v-windows-x64.zip"
        Get-QcDownload -Urls @($base) -OutFile $zip -Algorithm SHA512 -Checksum $sum
        Expand-QcArchive $zip $cfg.OPENSEARCH_DIR $cfg.TMP_DIR
    }
    New-Item -ItemType Directory -Force -Path $cfg.OPENSEARCH_DATA_DIR, (Join-Path $cfg.LOG_DIR 'opensearch') | Out-Null

    Write-QcTemplate 'opensearch.yml.tpl' (Join-Path $cfg.OPENSEARCH_DIR 'config\opensearch.yml') @{
        OPENSEARCH_VERSION  = $cfg.OPENSEARCH_VERSION
        OPENSEARCH_DATA_DIR = ConvertTo-QcForwardSlash $cfg.OPENSEARCH_DATA_DIR
        OPENSEARCH_PORT     = $cfg.OPENSEARCH_PORT
        LOG_DIR             = ConvertTo-QcForwardSlash $cfg.LOG_DIR
    }
    $jvmDir = Join-Path $cfg.OPENSEARCH_DIR 'config\jvm.options.d'
    New-Item -ItemType Directory -Force -Path $jvmDir | Out-Null
    Set-Content -LiteralPath (Join-Path $jvmDir 'quickcart-heap.options') -Encoding ASCII -Value @(
        "-Xms$($cfg.OPENSEARCH_HEAP)", "-Xmx$($cfg.OPENSEARCH_HEAP)"
    )
    Write-QcOk 'OpenSearch configured (single-node, loopback only)'
}

function Install-QcNginx($cfg) {
    Write-QcStep "Nginx $($cfg.NGINX_VERSION) (port $($cfg.HTTP_PORT))"
    if (-not (Test-Path -LiteralPath (Join-Path $cfg.NGINX_DIR 'nginx.exe'))) {
        $zip = Join-Path $cfg.DOWNLOAD_DIR "nginx-$($cfg.NGINX_VERSION).zip"
        Get-QcDownload -Urls @("https://nginx.org/download/nginx-$($cfg.NGINX_VERSION).zip") -OutFile $zip
        Expand-QcArchive $zip $cfg.NGINX_DIR $cfg.TMP_DIR
    }
    foreach ($d in @('logs', 'temp')) { New-Item -ItemType Directory -Force -Path (Join-Path $cfg.NGINX_DIR $d) | Out-Null }

    $upstreams = ($cfg.PHP_CGI_PORT_LIST | ForEach-Object { "        server 127.0.0.1:$_ max_fails=0;" }) -join "`r`n"
    Write-QcTemplate 'nginx.conf.tpl' (Join-Path $cfg.NGINX_DIR 'conf\nginx.conf') @{
        NGINX_VERSION = $cfg.NGINX_VERSION
        LOG_DIR       = ConvertTo-QcForwardSlash $cfg.LOG_DIR
        PHP_UPSTREAMS = $upstreams
        HTTP_PORT     = $cfg.HTTP_PORT
        SERVER_NAME   = $cfg.SERVER_NAME
        MAGE_ROOT     = ConvertTo-QcForwardSlash $cfg.MAGENTO_ROOT
    }
    Write-QcOk 'Nginx configured (Magento rules included from src/nginx.conf.sample)'
}

function Install-QcStack {
    Initialize-QcEnvFile
    $cfg = Get-QcConfig
    Write-QcStep "Stack directory: $($cfg.STACK_DIR)"
    foreach ($d in @($cfg.STACK_DIR, $cfg.DOWNLOAD_DIR, $cfg.COMPOSER_DIR, $cfg.CONF_DIR, $cfg.LOG_DIR, $cfg.TMP_DIR, $cfg.RUN_DIR, (Join-Path $cfg.STACK_DIR 'data'))) {
        New-Item -ItemType Directory -Force -Path $d | Out-Null
    }
    Install-QcPhp $cfg
    Install-QcComposer $cfg
    Install-QcMysql $cfg
    Install-QcOpenSearch $cfg
    Install-QcNginx $cfg
    Write-QcStep 'Stack installed. Next: devstack\qc install-magento'
}

# ----------------------------------------------------------------------------- services

function Start-QcMysql($cfg) {
    if (Test-QcPort $cfg.DB_PORT) { Write-QcOk "MySQL already listening on 127.0.0.1:$($cfg.DB_PORT)"; return }
    Start-Process -FilePath (Join-Path $cfg.MYSQL_DIR 'bin\mysqld.exe') `
        -ArgumentList "--defaults-file=$($cfg.MYSQL_INI)" -WindowStyle Hidden
    Wait-QcPort $cfg.DB_PORT 90 'MySQL'
    Write-QcOk "MySQL $($cfg.MYSQL_VERSION) on 127.0.0.1:$($cfg.DB_PORT)"
}

function Stop-QcMysql($cfg) {
    $procs = @(Get-QcProcess 'mysqld.exe' $cfg.MYSQL_DIR)
    if ($procs.Count -eq 0) { return }
    & (Join-Path $cfg.MYSQL_DIR 'bin\mysqladmin.exe') "--defaults-extra-file=$($cfg.MYSQL_ROOT_CNF)" shutdown
    $deadline = (Get-Date).AddSeconds(60)
    while (@(Get-QcProcess 'mysqld.exe' $cfg.MYSQL_DIR).Count -gt 0 -and (Get-Date) -lt $deadline) { Start-Sleep -Milliseconds 500 }
    Stop-QcProcessList (Get-QcProcess 'mysqld.exe' $cfg.MYSQL_DIR)
    Write-QcOk 'MySQL stopped'
}

function Start-QcOpenSearch($cfg) {
    if (Test-QcPort $cfg.OPENSEARCH_PORT) { Write-QcOk "OpenSearch already listening on 127.0.0.1:$($cfg.OPENSEARCH_PORT)"; return }
    # Always use the JDK bundled with OpenSearch (ignores any JAVA_HOME on the machine).
    $env:OPENSEARCH_JAVA_HOME = Join-Path $cfg.OPENSEARCH_DIR 'jdk'
    Start-Process -FilePath (Join-Path $cfg.OPENSEARCH_DIR 'bin\opensearch.bat') `
        -WorkingDirectory $cfg.OPENSEARCH_DIR -WindowStyle Hidden
    Wait-QcPort $cfg.OPENSEARCH_PORT 180 'OpenSearch'
    Write-QcOk "OpenSearch $($cfg.OPENSEARCH_VERSION) on http://127.0.0.1:$($cfg.OPENSEARCH_PORT)"
}

function Stop-QcOpenSearch($cfg) {
    $procs = @(Get-QcProcess 'java.exe' $cfg.OPENSEARCH_DIR)
    if ($procs.Count -eq 0) { return }
    Stop-QcProcessList $procs
    Write-QcOk 'OpenSearch stopped'
}

function Start-QcPhpCgi($cfg) {
    $stopFlag = Join-Path $cfg.RUN_DIR 'php-cgi.stop'
    if (Test-Path -LiteralPath $stopFlag) { Remove-Item -LiteralPath $stopFlag -Force }
    $worker = Join-Path $cfg.DEVSTACK_DIR 'lib\php-cgi-worker.ps1'
    foreach ($port in $cfg.PHP_CGI_PORT_LIST) {
        if (Test-QcPort $port) { continue }
        Start-Process -FilePath 'powershell.exe' -WindowStyle Hidden -ArgumentList @(
            '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', "`"$worker`"", '-Port', $port
        )
    }
    foreach ($port in $cfg.PHP_CGI_PORT_LIST) { Wait-QcPort $port 30 "php-cgi ($port)" }
    Write-QcOk ("PHP-CGI workers on ports " + ($cfg.PHP_CGI_PORT_LIST -join ', '))
}

function Stop-QcPhpCgi($cfg) {
    New-Item -ItemType File -Force -Path (Join-Path $cfg.RUN_DIR 'php-cgi.stop') | Out-Null
    Stop-QcProcessList (Get-QcProcess 'powershell.exe' 'php-cgi-worker.ps1')
    Stop-QcProcessList (Get-QcProcess 'php-cgi.exe' $cfg.PHP_DIR)
    Write-QcOk 'PHP-CGI workers stopped'
}

function Start-QcNginx($cfg) {
    if (-not (Test-Path -LiteralPath (Join-Path $cfg.MAGENTO_ROOT 'nginx.conf.sample'))) {
        Write-QcWarn 'Magento is not installed yet - Nginx not started'
        return
    }
    if (Test-QcPort $cfg.HTTP_PORT) { Write-QcOk "Nginx already listening on port $($cfg.HTTP_PORT)"; return }
    $nginx = Join-Path $cfg.NGINX_DIR 'nginx.exe'
    $prefix = (ConvertTo-QcForwardSlash $cfg.NGINX_DIR) + '/'
    Push-Location $cfg.NGINX_DIR
    try { & $nginx -p $prefix -t -q; Assert-QcExit 'nginx -t (configuration test)' } finally { Pop-Location }
    Start-Process -FilePath $nginx -ArgumentList @('-p', $prefix) -WorkingDirectory $cfg.NGINX_DIR -WindowStyle Hidden
    Wait-QcPort $cfg.HTTP_PORT 20 'Nginx'
    Write-QcOk "Nginx on $($cfg.BASE_URL)"
}

function Stop-QcNginx($cfg) {
    $procs = @(Get-QcProcess 'nginx.exe' $cfg.NGINX_DIR)
    if ($procs.Count -eq 0) { return }
    $prefix = (ConvertTo-QcForwardSlash $cfg.NGINX_DIR) + '/'
    Push-Location $cfg.NGINX_DIR
    try { & (Join-Path $cfg.NGINX_DIR 'nginx.exe') -p $prefix -s quit } finally { Pop-Location }
    Start-Sleep -Seconds 2
    Stop-QcProcessList (Get-QcProcess 'nginx.exe' $cfg.NGINX_DIR)
    Write-QcOk 'Nginx stopped'
}

function Start-QcStack {
    $cfg = Get-QcConfig
    Write-QcStep 'Starting QuickCart stack'
    Start-QcMysql $cfg
    Start-QcOpenSearch $cfg
    Start-QcPhpCgi $cfg
    Start-QcNginx $cfg
}

function Stop-QcStack {
    $cfg = Get-QcConfig
    Write-QcStep 'Stopping QuickCart stack'
    Stop-QcNginx $cfg
    Stop-QcPhpCgi $cfg
    Stop-QcOpenSearch $cfg
    Stop-QcMysql $cfg
}

function Get-QcHttpStatus($cfg, [string]$Path) {
    $uri = [Uri]$cfg.BASE_URL
    $resolve = "$($uri.Host):$($uri.Port):127.0.0.1"
    $code = & curl.exe -s -o NUL -w '%{http_code}' --max-time 120 --resolve $resolve ($cfg.BASE_URL.TrimEnd('/') + $Path)
    return "$code"
}

function Show-QcStatus {
    $cfg = Get-QcConfig
    $rows = @(
        [pscustomobject]@{ Service = 'MySQL';      Endpoint = "127.0.0.1:$($cfg.DB_PORT)";         Up = Test-QcPort $cfg.DB_PORT }
        [pscustomobject]@{ Service = 'OpenSearch'; Endpoint = "127.0.0.1:$($cfg.OPENSEARCH_PORT)"; Up = Test-QcPort $cfg.OPENSEARCH_PORT }
    )
    foreach ($port in $cfg.PHP_CGI_PORT_LIST) {
        $rows += [pscustomobject]@{ Service = 'PHP-CGI'; Endpoint = "127.0.0.1:$port"; Up = Test-QcPort $port }
    }
    $rows += [pscustomobject]@{ Service = 'Nginx'; Endpoint = $cfg.BASE_URL; Up = Test-QcPort $cfg.HTTP_PORT }
    $rows | Format-Table -AutoSize | Out-Host
    if (Test-QcPort $cfg.HTTP_PORT) {
        Write-QcInfo ("Storefront  " + $cfg.BASE_URL + "  -> HTTP " + (Get-QcHttpStatus $cfg '/'))
        Write-QcInfo ("Admin       " + $cfg.BASE_URL + $cfg.ADMIN_FRONTNAME + "  -> HTTP " + (Get-QcHttpStatus $cfg "/$($cfg.ADMIN_FRONTNAME)"))
    }
}
