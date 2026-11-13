<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Test\Unit\View\Design\Theme;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\View\Design\Theme\ThemePackage;
use Magento\Framework\View\Design\Theme\ThemePackageFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use QuickCart\WindowsCompat\Model\Platform;
use QuickCart\WindowsCompat\View\Design\Theme\StaticResourceThemePackageList;

#[CoversClass(StaticResourceThemePackageList::class)]
class StaticResourceThemePackageListTest extends TestCase
{
    public function testWindowsAddsBackslashAreaKeyForSameTheme(): void
    {
        $themes = $this->createList('\\')->getThemes();

        $this->assertSame(
            ['frontend/Magento/luma', 'frontend\\Magento/luma'],
            array_keys($themes)
        );
        $this->assertSame($themes['frontend/Magento/luma'], $themes['frontend\\Magento/luma']);
    }

    public function testOtherPlatformsReturnCoreListUnchanged(): void
    {
        $this->assertSame(['frontend/Magento/luma'], array_keys($this->createList('/')->getThemes()));
    }

    private function createList(string $separator): StaticResourceThemePackageList
    {
        $registrar = $this->createMock(ComponentRegistrarInterface::class);
        $registrar->expects($this->once())
            ->method('getPaths')
            ->with(ComponentRegistrar::THEME)
            ->willReturn(['frontend/Magento/luma' => 'E:/shop/src/vendor/magento/theme-frontend-luma']);

        $factory = $this->createStub(ThemePackageFactory::class);
        $factory->method('create')->willReturn($this->createStub(ThemePackage::class));

        return new StaticResourceThemePackageList($registrar, $factory, new Platform($separator));
    }
}
