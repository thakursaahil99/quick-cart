<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Image\Adapter;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\Adapter\Gd2 as CoreGd2;
use Psr\Log\LoggerInterface;
use QuickCart\WindowsCompat\Model\Platform;

/**
 * GD2 adapter that can open images addressed by a Windows drive path.
 *
 * Core's private validateURLScheme() runs parse_url() on the file name, which turns
 * the drive letter of "E:/media/x.jpg" into the URL scheme "E" and rejects the file
 * ("Wrong file"). That breaks theme registration during setup:install and every
 * product image resize. On Windows the image is opened through an equivalent path
 * relative to the working directory (no scheme for parse_url() to find); the original
 * absolute path is then restored so save()/resize() behave exactly as in core.
 */
class Gd2 extends CoreGd2
{
    /**
     * @var Platform
     */
    private Platform $platform;

    /**
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param array $data
     * @param Platform|null $platform
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger,
        array $data = [],
        ?Platform $platform = null
    ) {
        // Wired explicitly in etc/di.xml; the fallback mirrors core's BC pattern.
        $this->platform = $platform ?? ObjectManager::getInstance()->get(Platform::class);
        parent::__construct($filesystem, $logger, $data);
    }

    /**
     * @inheritdoc
     */
    public function open($filename)
    {
        $openPath = $filename;
        if (is_string($filename) && $this->platform->isWindows() && $this->platform->isDrivePath($filename)) {
            $cwd = getcwd();
            $relative = $cwd === false ? null : $this->platform->toRelativePath($filename, $cwd);
            $openPath = $relative ?? $filename;
        }

        parent::open($openPath);

        if ($openPath !== $filename) {
            $this->_fileName = $filename;
            $this->_getFileAttributes();
        }
    }
}
