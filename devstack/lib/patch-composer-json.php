<?php
/**
 * Turns the stock magento/project-community-edition composer.json into the
 * QuickCart project manifest:
 *  - project name/description
 *  - Magento package repository (Mage-OS mirror or repo.magento.com)
 *  - drops the MFTF Selenium suite (not used by this project; keeps installs lean)
 *
 * Usage: php patch-composer-json.php <path/to/composer.json> <repository-url>
 */
declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php patch-composer-json.php <composer.json> <repository-url>\n");
    exit(1);
}

[, $file, $repositoryUrl] = $argv;

if (!filter_var($repositoryUrl, FILTER_VALIDATE_URL) || !str_starts_with($repositoryUrl, 'https://')) {
    fwrite(STDERR, "Repository URL must be an https:// URL\n");
    exit(1);
}

$json = json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

$json['name'] = 'quickcart/magento2-store';
$json['description'] = 'QuickCart - Magento Open Source 2 eCommerce platform (Shop Smarter. Shop Faster.)';
$json['type'] = 'project';
$json['repositories'] = [
    'magento' => ['type' => 'composer', 'url' => $repositoryUrl],
];
unset($json['require-dev']['magento/magento2-functional-testing-framework']);
$json['config']['sort-packages'] = true;

$encoded = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
file_put_contents($file, $encoded . "\n");
echo "Patched $file\n";
