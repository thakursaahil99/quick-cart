<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Test\Unit\Controller\Recent;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use QuickCart\ProductRecommendation\Model\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\ProductRecommendation\Controller\Recent\Render;

#[CoversClass(Render::class)]
class RenderTest extends TestCase
{
    #[DataProvider('idsProvider')]
    public function testParseIds(string $input, int $max, array $expected): void
    {
        $this->assertSame($expected, $this->render()->parseIds($input, $max));
    }

    /**
     * @return array<string, array{string, int, int[]}>
     */
    public static function idsProvider(): array
    {
        return [
            'keeps order, drops duplicates' => ['27, 5,27,9', 12, [27, 5, 9]],
            'rejects non-numeric and sql' => ['5,1 OR 1=1,-3,abc,0,7', 12, [5, 7]],
            'caps the list' => ['1,2,3,4,5', 3, [1, 2, 3]],
            'rejects absurd lengths' => ['99999999999,4', 12, [4]],
            'empty' => ['', 12, []],
        ];
    }

    private function render(): Render
    {
        return new Render(
            $this->createStub(RequestInterface::class),
            $this->createStub(JsonFactory::class),
            $this->createStub(LayoutInterface::class),
            $this->createStub(Config::class)
        );
    }
}
