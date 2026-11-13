<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\View\Design\Theme;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\View\Design\Theme\ThemePackageFactory;
use Magento\Framework\View\Design\Theme\ThemePackageList;
use QuickCart\WindowsCompat\Model\Platform;

/**
 * Theme package list used only by Magento\Framework\App\StaticResource (pub/static.php).
 *
 * StaticResource::launch() checks the requested theme with
 * `$area . DIRECTORY_SEPARATOR . $theme`, i.e. "frontend\Magento/luma" on Windows,
 * while themes are registered as "frontend/Magento/luma". Every on-demand static file
 * is therefore rejected with "Requested path ... is wrong". On Windows this list also
 * exposes each theme under that separator variant. It is injected into StaticResource
 * alone (see etc/di.xml), so theme registration and every other consumer are unaffected.
 */
class StaticResourceThemePackageList extends ThemePackageList
{
    /**
     * @var Platform
     */
    private Platform $platform;

    /**
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ThemePackageFactory $factory
     * @param Platform|null $platform
     */
    public function __construct(
        ComponentRegistrarInterface $componentRegistrar,
        ThemePackageFactory $factory,
        ?Platform $platform = null
    ) {
        // Wired explicitly in etc/di.xml; the fallback mirrors core's BC pattern.
        $this->platform = $platform ?? ObjectManager::getInstance()->get(Platform::class);
        parent::__construct($componentRegistrar, $factory);
    }

    /**
     * @inheritdoc
     */
    public function getThemes()
    {
        $themes = parent::getThemes();
        if (!$this->platform->isWindows()) {
            return $themes;
        }

        foreach ($themes as $key => $theme) {
            $parts = explode('/', (string)$key, 2);
            if (count($parts) === 2) {
                $themes[$parts[0] . '\\' . $parts[1]] = $theme;
            }
        }

        return $themes;
    }
}
