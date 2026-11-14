<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Test\Unit\Model\Builder;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use QuickCart\ProductRecommendation\Model\Builder\SimilarProductsBuilder;

#[CoversClass(SimilarProductsBuilder::class)]
class SimilarProductsBuilderTest extends TestCase
{
    public function testNoSharedCategoryMeansNotSimilar(): void
    {
        $this->assertSame(0.0, $this->builder()->score(
            $this->product([12], [3, 12], 1, 1000.0),
            $this->product([20], [5, 20], 1, 1000.0)
        ));
    }

    public function testSameLeafSameBrandSamePriceScoresHighest(): void
    {
        $phone = $this->product([12], [3, 12], 7, 50000.0);
        $twin = $this->product([12], [3, 12], 7, 50000.0);
        $cheaperSibling = $this->product([12], [3, 12], 9, 25000.0);
        $sameTopOnly = $this->product([13], [3, 13], 7, 50000.0);

        $twinScore = $this->builder()->score($phone, $twin);
        $this->assertSame(
            SimilarProductsBuilder::WEIGHT_SHARED_CATEGORY + SimilarProductsBuilder::WEIGHT_LEAF_CATEGORY
            + SimilarProductsBuilder::WEIGHT_BRAND + SimilarProductsBuilder::WEIGHT_PRICE,
            $twinScore
        );
        // Same leaf, other brand, half the price: 1 + 3 + 2 * 0.5.
        $this->assertSame(5.0, $this->builder()->score($phone, $cheaperSibling));
        $this->assertGreaterThan($this->builder()->score($phone, $sameTopOnly), $twinScore);
    }

    public function testScoreIsSymmetric(): void
    {
        $a = $this->product([12], [3, 12], 7, 30000.0);
        $b = $this->product([12], [3, 12], null, 45000.0);

        $this->assertSame($this->builder()->score($a, $b), $this->builder()->score($b, $a));
    }

    /**
     * @param int[] $leaf
     * @param int[] $categories
     * @param int|null $brand
     * @param float $price
     * @return array{leaf: int[], categories: int[], brand: int|null, price: float}
     */
    private function product(array $leaf, array $categories, ?int $brand, float $price): array
    {
        return ['leaf' => $leaf, 'categories' => $categories, 'brand' => $brand, 'price' => $price];
    }

    private function builder(): SimilarProductsBuilder
    {
        return new SimilarProductsBuilder(
            $this->createStub(CollectionFactory::class),
            $this->createStub(ResourceConnection::class),
            $this->createStub(StoreManagerInterface::class)
        );
    }
}
