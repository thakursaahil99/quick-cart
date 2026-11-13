<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Model\Product\Gallery;

/**
 * Normalises the gallery file name returned after moving an image out of tmp.
 *
 * Core CreateHandler::getSafeFilename() prefixes DIRECTORY_SEPARATOR, so on Windows
 * "\/q/c/x.jpg" becomes "//q/c/x.jpg" and is stored like that. Media lookups by
 * "/q/c/x.jpg" (on-demand resize in get.php, image role matching) then fail.
 */
trait NormalizedTmpImageMove
{
    /**
     * @inheritdoc
     */
    protected function moveImageFromTmp($file)
    {
        $result = (string)parent::moveImageFromTmp($file);

        return DIRECTORY_SEPARATOR === '\\' ? '/' . ltrim($result, '/') : $result;
    }
}
