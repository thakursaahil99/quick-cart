#Requires -Version 5.1
<#
    Supervises one php-cgi FastCGI worker (Windows has no php-fpm).
    php-cgi exits after PHP_FCGI_MAX_REQUESTS (5000) requests to bound memory;
    this loop restarts it until `qc stop` drops the stop flag.
    Started hidden by Start-QcPhpCgi - not meant to be run by hand.
#>
param([Parameter(Mandatory = $true)][int]$Port)

. (Join-Path $PSScriptRoot 'QuickCart.DevStack.ps1')
$ErrorActionPreference = 'Continue'

$cfg = Get-QcConfig
$stopFlag = Join-Path $cfg.RUN_DIR 'php-cgi.stop'
$env:PHP_FCGI_MAX_REQUESTS = '5000'
Set-QcToolEnvironment $cfg

while (-not (Test-Path -LiteralPath $stopFlag)) {
    & $cfg.PHP_CGI_EXE -b "127.0.0.1:$Port"
    Start-Sleep -Milliseconds 300
}
