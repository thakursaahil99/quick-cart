<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Test\Unit\Model\Builder;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use QuickCart\ProductRecommendation\Model\Builder\BoughtTogetherBuilder;

#[CoversClass(BoughtTogetherBuilder::class)]
class BoughtTogetherBuilderTest extends TestCase
{
    public function testPartnersAreRankedBySharedOrdersAndCapped(): void
    {
        $pairs = [
            ['product_id' => '5', 'recommended_product_id' => '9', 'orders' => '2'],
            ['product_id' => '5', 'recommended_product_id' => '7', 'orders' => '6'],
            ['product_id' => '5', 'recommended_product_id' => '3', 'orders' => '2'],
            ['product_id' => '5', 'recommended_product_id' => '8', 'orders' => '1'],
            ['product_id' => '7', 'recommended_product_id' => '5', 'orders' => '6'],
        ];

        $rows = $this->builder()->rank($pairs, 3);

        $this->assertSame(
            [
                ['product_id' => 5, 'recommended_product_id' => 7, 'score' => 6.0],
                // Ties keep the lower product ID first, so rebuilds are stable.
                ['product_id' => 5, 'recommended_product_id' => 3, 'score' => 2.0],
                ['product_id' => 5, 'recommended_product_id' => 9, 'score' => 2.0],
                ['product_id' => 7, 'recommended_product_id' => 5, 'score' => 6.0],
            ],
            $rows
        );
    }

    public function testSelfPairsAndInvalidIdsAreDropped(): void
    {
        $rows = $this->builder()->rank([
            ['product_id' => 4, 'recommended_product_id' => 4, 'orders' => 3],
            ['product_id' => 0, 'recommended_product_id' => 2, 'orders' => 3],
        ], 4);

        $this->assertSame([], $rows);
    }

    private function builder(): BoughtTogetherBuilder
    {
        return new BoughtTogetherBuilder(
            $this->createStub(ResourceConnection::class),
            $this->createStub(DateTime::class)
        );
    }
}
