<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Test\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\WindowsCompat\Model\Platform;

#[CoversClass(Platform::class)]
class PlatformTest extends TestCase
{
    public function testDetectsWindowsFromDirectorySeparator(): void
    {
        $this->assertTrue((new Platform('\\'))->isWindows());
        $this->assertFalse((new Platform('/'))->isWindows());
    }

    public function testNormalizesBackslashes(): void
    {
        $this->assertSame(
            'E:/shop/src/vendor/magento/module-theme/view/frontend/templates/html/title.phtml',
            (new Platform('\\'))->normalizePath(
                'E:\\shop\\src\\vendor\\magento\\module-theme\\view\\frontend\\templates\\html\\title.phtml'
            )
        );
    }

    #[DataProvider('pathInsideProvider')]
    public function testIsPathInside(string $path, string $directory, bool $expected): void
    {
        $this->assertSame($expected, (new Platform('\\'))->isPathInside($path, $directory));
    }

    public function testNormalizesWindowsBuiltUrls(): void
    {
        $this->assertSame(
            'http://shop.localhost:8080/media/catalog/product/cache/9bb9d6/q/c/qc-ac-power20k_1.jpg',
            (new Platform('\\'))->normalizeUrl(
                'http://shop.localhost:8080/media/catalog/product\\cache\\9bb9d6\\//q/c/qc-ac-power20k_1.jpg'
            )
        );
        $this->assertSame(
            'https://cdn.example.com/a/b.jpg',
            (new Platform('\\'))->normalizeUrl('https://cdn.example.com/a/b.jpg')
        );
    }

    public function testDetectsDrivePaths(): void
    {
        $platform = new Platform('\\');
        $this->assertTrue($platform->isDrivePath('E:/shop/pub/media/x.jpg'));
        $this->assertTrue($platform->isDrivePath('e:\\shop\\x.jpg'));
        $this->assertFalse($platform->isDrivePath('/var/www/pub/media/x.jpg'));
        $this->assertFalse($platform->isDrivePath('https://cdn.example.com/x.jpg'));
        $this->assertFalse($platform->isDrivePath('../media/x.jpg'));
    }

    #[DataProvider('relativePathProvider')]
    public function testToRelativePath(string $path, string $base, ?string $expected): void
    {
        $this->assertSame($expected, (new Platform('\\'))->toRelativePath($path, $base));
    }

    /**
     * @return array<string, array{string, string, string|null}>
     */
    public static function relativePathProvider(): array
    {
        return [
            'sibling tree'         => ['E:\\shop\\src\\vendor\\a\\x.jpg', 'E:\\shop\\src\\pub', '../vendor/a/x.jpg'],
            'below base'           => ['E:/shop/src/pub/media/x.jpg', 'E:/shop/src/pub', 'media/x.jpg'],
            'base has trailing /'  => ['E:/shop/src/pub/media/x.jpg', 'E:/shop/src/pub/', 'media/x.jpg'],
            'drive case differs'   => ['e:/shop/x.jpg', 'E:/shop/src', '../x.jpg'],
            'different drive'      => ['D:/media/x.jpg', 'E:/shop/src', null],
        ];
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function pathInsideProvider(): array
    {
        return [
            'backslash path in slash dir' => ['E:\\shop\\src\\app\\code\\A\\t.phtml', 'E:/shop/src/app/code/A', true],
            'drive letter case differs'   => ['e:\\shop\\src\\vendor\\x.phtml', 'E:/shop/src/vendor', true],
            'outside directory'           => ['E:\\other\\x.phtml', 'E:/shop/src', false],
            'empty directory never match' => ['E:\\shop\\x.phtml', '', false],
        ];
    }
}
