<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Test\Unit\Model;

use DateTimeImmutable;
use Magento\Framework\Exception\InputException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\CustomApi\Model\DateRange;

#[CoversClass(DateRange::class)]
class DateRangeTest extends TestCase
{
    private const TODAY = '2026-11-14';

    public function testDefaultsToTheLastThirtyDays(): void
    {
        $this->assertSame(['2026-10-16', '2026-11-14'], $this->resolve(null, null));
        $this->assertSame(['2026-10-16', '2026-11-14'], $this->resolve('', ' '));
    }

    public function testExplicitRangeAndOpenStart(): void
    {
        $this->assertSame(['2026-01-01', '2026-01-31'], $this->resolve('2026-01-01', '2026-01-31'));
        $this->assertSame(['2026-01-02', '2026-01-31'], $this->resolve(null, '2026-01-31'));
        $this->assertSame(['2026-11-14', '2026-11-14'], $this->resolve('2026-11-14', null));
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidRangesAreRejected(?string $from, ?string $to): void
    {
        $this->expectException(InputException::class);
        $this->resolve($from, $to);
    }

    /**
     * @return array<string, array{string|null, string|null}>
     */
    public static function invalidProvider(): array
    {
        return [
            'reversed' => ['2026-12-01', '2026-11-01'],
            'not a date' => ['yesterday', null],
            'impossible day' => ['2026-02-30', null],
            'wrong format' => ['14/11/2026', null],
            'longer than a year' => ['2025-01-01', '2026-11-14'],
        ];
    }

    /**
     * @param string|null $from
     * @param string|null $to
     * @return array{0: string, 1: string}
     */
    private function resolve(?string $from, ?string $to): array
    {
        return (new DateRange())->resolve($from, $to, new DateTimeImmutable(self::TODAY));
    }
}
