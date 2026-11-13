<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Plugin\View\Asset;

use Magento\Framework\App\View\Asset\MaterializationStrategy\Symlink;
use Magento\Framework\View\Asset\LocalInterface;
use QuickCart\WindowsCompat\Model\Platform;

/**
 * Makes the Symlink materialization strategy decline on Windows so the
 * strategy factory falls through to the Copy strategy.
 */
class DisableSymlinkStrategyOnWindows
{
    /**
     * @param Platform $platform
     */
    public function __construct(
        private readonly Platform $platform
    ) {
    }

    /**
     * After plugin for Symlink::isSupported().
     *
     * @param Symlink $subject
     * @param bool $result
     * @param LocalInterface $asset
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsSupported(Symlink $subject, $result, LocalInterface $asset): bool
    {
        return (bool)$result && !$this->platform->isWindows();
    }
}
