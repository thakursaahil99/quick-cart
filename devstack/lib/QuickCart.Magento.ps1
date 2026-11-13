#Requires -Version 5.1
<#
    QuickCart - Magento Open Source installation on the native Windows stack.
    Idempotent: every step is skipped when its result already exists, so the same
    command bootstraps a brand-new project or installs a fresh clone of the repo.
#>

function Initialize-QcComposerProject($cfg) {
    $root = $cfg.MAGENTO_ROOT
    $composerJson = Join-Path $root 'composer.json'
    if (Test-Path -LiteralPath $composerJson) {
        Write-QcOk 'src/composer.json present (cloned project)'
        return
    }
    Write-QcStep "Bootstrapping src/composer.json from magento/project-community-edition $($cfg.MAGENTO_VERSION)"
    $boot = Join-Path $cfg.TMP_DIR 'project-bootstrap'
    if (Test-Path -LiteralPath $boot) { Remove-Item -LiteralPath $boot -Recurse -Force }
    Invoke-QcComposer $cfg @(
        'create-project', '--no-install', '--no-interaction', '--no-ansi',
        "--repository-url=$($cfg.MAGENTO_COMPOSER_REPO)",
        "magento/project-community-edition=$($cfg.MAGENTO_VERSION)", $boot
    )
    Assert-QcExit 'composer create-project'
    New-Item -ItemType Directory -Force -Path $root | Out-Null
    Copy-Item -LiteralPath (Join-Path $boot 'composer.json') -Destination $composerJson
    Remove-Item -LiteralPath $boot -Recurse -Force
    Invoke-QcPhp $cfg @((Join-Path $cfg.DEVSTACK_DIR 'lib\patch-composer-json.php'), $composerJson, $cfg.MAGENTO_COMPOSER_REPO)
    Assert-QcExit 'Patching composer.json'
}

function Install-QcComposerPackages($cfg) {
    if (Test-Path -LiteralPath (Join-Path $cfg.MAGENTO_ROOT 'vendor\autoload.php')) {
        Write-QcOk 'Composer dependencies already installed'
        return
    }
    $repo = $cfg.MAGENTO_COMPOSER_REPO
    if ($repo -like '*repo.magento.com*' -and -not (Test-Path -LiteralPath (Join-Path $cfg.MAGENTO_ROOT 'auth.json'))) {
        throw 'repo.magento.com needs src/auth.json with your Commerce Marketplace keys (see src/auth.json.sample)'
    }
    Write-QcStep 'composer install (first run downloads ~400 MB; Windows Defender scanning makes this slow)'
    Invoke-QcComposer $cfg @('install', '--no-interaction', '--no-ansi', '--prefer-dist', "--working-dir=$($cfg.MAGENTO_ROOT)")
    Assert-QcExit 'composer install'
}

function Invoke-QcSetupInstall($cfg) {
    if (Test-Path -LiteralPath (Join-Path $cfg.MAGENTO_ROOT 'app\etc\env.php')) {
        Write-QcOk 'app/etc/env.php exists - running setup:upgrade instead of setup:install'
        Invoke-QcMagento $cfg @('setup:upgrade', '--no-interaction')
        Assert-QcExit 'setup:upgrade'
        return
    }
    Write-QcStep "bin/magento setup:install (Magento $($cfg.MAGENTO_VERSION))"
    Invoke-QcMagento $cfg @(
        'setup:install', '--no-interaction',
        "--base-url=$($cfg.BASE_URL)",
        "--db-host=$($cfg.DB_HOST):$($cfg.DB_PORT)",
        "--db-name=$($cfg.DB_NAME)",
        "--db-user=$($cfg.DB_USER)",
        "--db-password=$($cfg.DB_PASSWORD)",
        "--backend-frontname=$($cfg.ADMIN_FRONTNAME)",
        "--admin-firstname=$($cfg.ADMIN_FIRSTNAME)",
        "--admin-lastname=$($cfg.ADMIN_LASTNAME)",
        "--admin-email=$($cfg.ADMIN_EMAIL)",
        "--admin-user=$($cfg.ADMIN_USER)",
        "--admin-password=$($cfg.ADMIN_PASSWORD)",
        "--language=$($cfg.LANGUAGE)",
        "--currency=$($cfg.CURRENCY)",
        "--timezone=$($cfg.TIMEZONE)",
        '--use-rewrites=1',
        '--search-engine=opensearch',
        "--opensearch-host=$($cfg.OPENSEARCH_HOST)",
        "--opensearch-port=$($cfg.OPENSEARCH_PORT)",
        "--opensearch-index-prefix=$($cfg.OPENSEARCH_INDEX_PREFIX)",
        '--opensearch-enable-auth=0',
        '--opensearch-timeout=30',
        '--session-save=files',
        # Local only: admin 2FA needs a mail server/authenticator. Re-enable for any shared or production env.
        '--disable-modules=Magento_AdminAdobeImsTwoFactorAuth,Magento_TwoFactorAuth'
    )
    Assert-QcExit 'setup:install'
}

function Set-QcDevelopmentConfig($cfg) {
    Write-QcStep 'Developer configuration'
    $settings = [ordered]@{
        'system/smtp/disable'                = '1'       # no local mail server
        'admin/security/session_lifetime'    = '28800'   # 8h admin session while developing
        'web/seo/use_rewrites'               = '1'
        'catalog/search/engine'              = 'opensearch'
        # Windows: pub/get.php's cached fast path compares a "\"-separated __DIR__ path with the
        # "/"-separated media path and answers 404. A zero lifetime always uses the full media app.
        'system/media_storage_configuration/configuration_update_time' = '0'
    }
    foreach ($path in $settings.Keys) {
        Invoke-QcMagento $cfg @('config:set', '--no-interaction', $path, $settings[$path])
        Assert-QcExit "config:set $path"
    }
    $mode = (Invoke-QcMagento $cfg @('deploy:mode:show') | Out-String)
    if ($mode -notmatch 'developer') {
        Invoke-QcMagento $cfg @('deploy:mode:set', 'developer')
        Assert-QcExit 'deploy:mode:set developer'
    }
    Invoke-QcMagento $cfg @('indexer:reindex')
    Assert-QcExit 'indexer:reindex'
    # Pre-generate every product image size declared in the theme's view.xml.
    Invoke-QcMagento $cfg @('catalog:images:resize')
    Assert-QcExit 'catalog:images:resize'
    Invoke-QcMagento $cfg @('cache:flush')
    Assert-QcExit 'cache:flush'
}

function Install-QcMagento {
    $cfg = Get-QcConfig
    if (-not (Test-Path -LiteralPath $cfg.PHP_EXE)) { throw 'Stack not installed - run: devstack\qc install-stack' }
    Write-QcStep 'Starting MySQL and OpenSearch'
    Start-QcMysql $cfg
    Start-QcOpenSearch $cfg

    Initialize-QcComposerProject $cfg
    Install-QcComposerPackages $cfg
    Invoke-QcSetupInstall $cfg
    Set-QcDevelopmentConfig $cfg

    Start-QcPhpCgi $cfg
    Start-QcNginx $cfg

    Write-QcStep 'QuickCart is running'
    Write-QcInfo "Storefront : $($cfg.BASE_URL)"
    Write-QcInfo "Admin      : $($cfg.BASE_URL)$($cfg.ADMIN_FRONTNAME)"
    Write-QcInfo "Admin user : $($cfg.ADMIN_USER)  (password: ADMIN_PASSWORD in devstack/.env)"
}
