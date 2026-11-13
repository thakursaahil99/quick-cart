<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Test\Unit\ViewModel;

use DateTime;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceInfo\Base as PriceInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\CatalogEnhancement\ViewModel\ProductBadges;

#[CoversClass(ProductBadges::class)]
class ProductBadgesTest extends TestCase
{
    private const TODAY = '2026-11-13';

    #[DataProvider('discountProvider')]
    public function testDiscountPercent(float $regular, float $final, int $expected): void
    {
        $product = $this->createProduct($regular, $final);

        $this->assertSame($expected, $this->createViewModel()->getDiscountPercent($product));
    }

    /**
     * @return array<string, array{float, float, int}>
     */
    public static function discountProvider(): array
    {
        return [
            'special price rounds down' => [2999.0, 2299.0, 23],
            'no discount'               => [4999.0, 4999.0, 0],
            'final above regular'       => [100.0, 120.0, 0],
            'zero regular price'        => [0.0, 0.0, 0],
        ];
    }

    #[DataProvider('newRangeProvider')]
    public function testIsNewHonoursDateRange(?string $from, ?string $to, bool $expected): void
    {
        $product = $this->createProduct(100.0, 100.0, ['news_from_date' => $from, 'news_to_date' => $to]);

        $this->assertSame($expected, $this->createViewModel()->isNew($product));
    }

    /**
     * @return array<string, array{string|null, string|null, bool}>
     */
    public static function newRangeProvider(): array
    {
        return [
            'no dates'            => [null, null, false],
            'open-ended, started' => ['2026-11-01 00:00:00', null, true],
            'starts tomorrow'     => ['2026-11-14 00:00:00', null, false],
            'ended yesterday'     => ['2026-10-01 00:00:00', '2026-11-12 00:00:00', false],
            'ends today'          => ['2026-11-01 00:00:00', '2026-11-13 00:00:00', true],
        ];
    }

    public function testBadgesAreOrderedAndCapped(): void
    {
        $product = $this->createProduct(1000.0, 800.0, [
            'news_from_date' => '2026-11-01 00:00:00',
            'qc_is_trending' => 1,
        ]);

        $this->assertSame(
            [['code' => 'sale', 'label' => '-20%'], ['code' => 'new', 'label' => 'New']],
            $this->createViewModel()->getBadges($product)
        );
    }

    private function createViewModel(): ProductBadges
    {
        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('date')->willReturnCallback(static fn () => new DateTime(self::TODAY . ' 12:00:00'));

        return new ProductBadges($timezone);
    }

    /**
     * @param float $regular
     * @param float $final
     * @param array<string, mixed> $data
     * @return Product
     */
    private function createProduct(float $regular, float $final, array $data = []): Product
    {
        $prices = ['regular_price' => $regular, 'final_price' => $final];
        $priceInfo = $this->createStub(PriceInfo::class);
        $priceInfo->method('getPrice')->willReturnCallback(function (string $code) use ($prices) {
            $amount = $this->createStub(AmountInterface::class);
            $amount->method('getValue')->willReturn($prices[$code]);
            $price = $this->createStub(PriceInterface::class);
            $price->method('getAmount')->willReturn($amount);

            return $price;
        });

        $product = $this->createStub(Product::class);
        $product->method('getPriceInfo')->willReturn($priceInfo);
        $product->method('getData')->willReturnCallback(static fn (string $key) => $data[$key] ?? null);

        return $product;
    }
}
