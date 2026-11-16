<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Test\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\SalesDashboard\Api\Data\SalesTargetInterface;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesTarget as SalesTargetResource;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesTarget\CollectionFactory;
use QuickCart\SalesDashboard\Model\SalesTargetFactory;
use QuickCart\SalesDashboard\Model\SalesTargetRepository;

#[CoversClass(SalesTargetRepository::class)]
class SalesTargetRepositoryTest extends TestCase
{
    #[DataProvider('targetProvider')]
    public function testValidation(string $period, float $revenue, int $orders, ?string $note, int $errors): void
    {
        $target = $this->createStub(SalesTargetInterface::class);
        $target->method('getPeriod')->willReturn($period);
        $target->method('getRevenueTarget')->willReturn($revenue);
        $target->method('getOrdersTarget')->willReturn($orders);
        $target->method('getNote')->willReturn($note);

        $repository = new SalesTargetRepository(
            $this->createStub(SalesTargetResource::class),
            $this->createStub(SalesTargetFactory::class),
            $this->createStub(CollectionFactory::class)
        );

        $this->assertCount($errors, $repository->validate($target));
    }

    /**
     * @return array<string, array{string, float, int, string|null, int}>
     */
    public static function targetProvider(): array
    {
        return [
            'valid' => ['2026-11', 1300000.0, 45, 'Wedding season', 0],
            'month 13' => ['2026-13', 1000.0, 1, null, 1],
            'wrong format' => ['11/2026', 1000.0, 1, null, 1],
            'zero revenue' => ['2026-11', 0.0, 1, null, 1],
            'negative orders' => ['2026-11', 1000.0, -1, null, 1],
            'note too long' => ['2026-11', 1000.0, 1, str_repeat('x', 256), 1],
            'everything wrong' => ['1999-01', -5.0, -1, null, 3],
        ];
    }
}
