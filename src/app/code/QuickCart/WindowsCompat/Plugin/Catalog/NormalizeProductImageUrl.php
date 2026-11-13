<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Plugin\Catalog;

use Magento\Catalog\Model\View\Asset\Image;
use QuickCart\WindowsCompat\Model\Platform;

/**
 * Core Image::getUrl() joins the media URL and the cache path with DIRECTORY_SEPARATOR,
 * producing ".../product\cache\<hash>\//q/c/x.jpg" on Windows (404 for every resized
 * product image). Normalises the URL on Windows only.
 */
class NormalizeProductImageUrl
{
    /**
     * @param Platform $platform
     */
    public function __construct(
        private readonly Platform $platform
    ) {
    }

    /**
     * After plugin for Image::getUrl().
     *
     * @param Image $subject
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetUrl(Image $subject, $result): string
    {
        return $this->platform->isWindows() ? $this->platform->normalizeUrl((string)$result) : (string)$result;
    }
}
