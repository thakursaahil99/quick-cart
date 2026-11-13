<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Model\Product\Gallery;

use Magento\Catalog\Model\Product\Gallery\UpdateHandler as CoreUpdateHandler;

/**
 * Media gallery update handler with Windows-safe stored file names.
 */
class UpdateHandler extends CoreUpdateHandler
{
    use NormalizedTmpImageMove;
}
