<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Test\Unit\Model;

use Magento\Framework\Exception\InputException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\CustomApi\Model\ProductQuery;

#[CoversClass(ProductQuery::class)]
class ProductQueryTest extends TestCase
{
    public function testPagingIsClamped(): void
    {
        $result = (new ProductQuery())->normalize('price', 'desc', -3, 500, false);

        $this->assertSame(['attribute' => 'price', 'direction' => 'DESC', 'page' => 1, 'page_size' => 48], $result);
    }

    public function testPositionOutsideACategoryFallsBackToNewest(): void
    {
        $query = new ProductQuery();

        $this->assertSame('created_at', $query->normalize('position', 'asc', 1, 12, false)['attribute']);
        $this->assertSame('DESC', $query->normalize('position', 'asc', 1, 12, false)['direction']);
        $this->assertSame('position', $query->normalize('Position', 'ASC', 1, 12, true)['attribute']);
    }

    #[DataProvider('invalidSortProvider')]
    public function testInvalidSortingIsRejected(string $sortBy, string $sortDir): void
    {
        $this->expectException(InputException::class);
        (new ProductQuery())->normalize($sortBy, $sortDir, 1, 12, true);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidSortProvider(): array
    {
        return [
            'unknown field' => ['entity_id; DROP TABLE x', 'asc'],
            'unknown direction' => ['name', 'up'],
        ];
    }

    public function testSearchWildcardsAreEscaped(): void
    {
        $query = new ProductQuery();

        $this->assertSame('%50\%\_off%', $query->likePattern(' 50%_off '));
        $this->assertNull($query->likePattern('   '));
        $this->assertNull($query->likePattern(null));
    }

    public function testOverlongSearchIsRejected(): void
    {
        $this->expectException(InputException::class);
        (new ProductQuery())->likePattern(str_repeat('a', 101));
    }

    public function testPriceRange(): void
    {
        $query = new ProductQuery();
        $query->assertPriceRange(null, 5000.0);
        $query->assertPriceRange(100.0, 100.0);

        $this->expectException(InputException::class);
        $query->assertPriceRange(900.0, 10.0);
    }
}
