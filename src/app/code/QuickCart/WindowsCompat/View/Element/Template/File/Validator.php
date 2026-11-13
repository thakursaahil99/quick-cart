<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\View\Element\Template\File;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\View\Element\Template\File\Validator as CoreValidator;
use QuickCart\WindowsCompat\Model\Platform;

/**
 * Template file validator that tolerates Windows path separators and drive-letter case.
 *
 * Core compares realpath() output ("E:\src\...") with directories registered by
 * ComponentRegistrar ("E:/src/..."), which never matches on Windows. On every
 * other platform this class defers to the core implementation unchanged.
 */
class Validator extends CoreValidator
{
    /**
     * @var FileDriver
     */
    private FileDriver $pathDriver;

    /**
     * @var Platform
     */
    private Platform $platform;

    /**
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param ComponentRegistrar $componentRegistrar
     * @param string|null $scope
     * @param FileDriver|null $fileDriver
     * @param Platform|null $platform
     */
    public function __construct(
        Filesystem $filesystem,
        ScopeConfigInterface $scopeConfigInterface,
        ComponentRegistrar $componentRegistrar,
        $scope = null,
        ?FileDriver $fileDriver = null,
        ?Platform $platform = null
    ) {
        // Both are wired explicitly in etc/di.xml; the fallback mirrors core's BC pattern.
        $this->pathDriver = $fileDriver ?? ObjectManager::getInstance()->get(FileDriver::class);
        $this->platform = $platform ?? ObjectManager::getInstance()->get(Platform::class);
        parent::__construct($filesystem, $scopeConfigInterface, $componentRegistrar, $scope, $this->pathDriver);
    }

    /**
     * @inheritdoc
     */
    protected function isPathInDirectories($path, $directories)
    {
        if (!$this->platform->isWindows()) {
            return parent::isPathInDirectories($path, $directories);
        }

        $realPath = $this->pathDriver->getRealPath($path);
        if (!is_string($realPath) || $realPath === '') {
            return false;
        }

        foreach ((array)$directories as $directory) {
            if ($directory !== null && $this->platform->isPathInside($realPath, (string)$directory)) {
                return true;
            }
        }

        return false;
    }
}
