<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Model\Product\Gallery;

use Magento\Catalog\Model\Product\Gallery\CreateHandler as CoreCreateHandler;

/**
 * Media gallery create handler with Windows-safe stored file names.
 */
class CreateHandler extends CoreCreateHandler
{
    use NormalizedTmpImageMove;
}
