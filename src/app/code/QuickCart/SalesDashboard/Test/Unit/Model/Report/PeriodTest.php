<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Test\Unit\Model\Report;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\SalesDashboard\Model\Report\Period;
use QuickCart\SalesDashboard\Model\Source\RangePreset;

#[CoversClass(Period::class)]
class PeriodTest extends TestCase
{
    private const TODAY = '2026-11-16 15:20:00';

    #[DataProvider('presetProvider')]
    public function testPresetsAndPreviousPeriod(string $preset, array $expected): void
    {
        $period = $this->period()->resolve($preset, null, null, new DateTimeImmutable(self::TODAY));

        $this->assertSame($expected, [$period['from'], $period['to'], $period['days'], $period['prev_from'], $period['prev_to']]);
    }

    /**
     * @return array<string, array{string, array}>
     */
    public static function presetProvider(): array
    {
        return [
            'today' => [RangePreset::TODAY, ['2026-11-16', '2026-11-16', 1, '2026-11-15', '2026-11-15']],
            'last 7 days' => [RangePreset::LAST_7, ['2026-11-10', '2026-11-16', 7, '2026-11-03', '2026-11-09']],
            'last 30 days' => [RangePreset::LAST_30, ['2026-10-18', '2026-11-16', 30, '2026-09-18', '2026-10-17']],
            'month to date' => [RangePreset::MONTH_TO_DATE, ['2026-11-01', '2026-11-16', 16, '2026-10-16', '2026-10-31']],
            'unknown falls back' => ['forever', ['2026-10-18', '2026-11-16', 30, '2026-09-18', '2026-10-17']],
        ];
    }

    public function testCustomRange(): void
    {
        $period = $this->period()->resolve('custom', '2026-01-01', '2026-01-31', new DateTimeImmutable(self::TODAY));

        $this->assertSame('custom', $period['preset']);
        $this->assertSame(31, $period['days']);
        $this->assertSame(['2025-12-01', '2025-12-31'], [$period['prev_from'], $period['prev_to']]);
    }

    #[DataProvider('invalidCustomProvider')]
    public function testInvalidCustomRangeFallsBackToDefault(?string $from, ?string $to): void
    {
        $period = $this->period()->resolve('custom', $from, $to, new DateTimeImmutable(self::TODAY), RangePreset::LAST_7);

        $this->assertSame(RangePreset::LAST_7, $period['preset']);
        $this->assertSame(7, $period['days']);
    }

    /**
     * @return array<string, array{string|null, string|null}>
     */
    public static function invalidCustomProvider(): array
    {
        return [
            'reversed' => ['2026-02-01', '2026-01-01'],
            'not a date' => ['2026-02-30', '2026-03-01'],
            'injection' => ["2026-01-01' OR 1=1", '2026-02-01'],
            'longer than a year' => ['2024-01-01', '2026-01-01'],
            'missing' => [null, '2026-01-01'],
        ];
    }

    public function testDaysListsEveryDay(): void
    {
        $this->assertSame(
            ['2026-02-27', '2026-02-28', '2026-03-01'],
            $this->period()->days('2026-02-27', '2026-03-01')
        );
    }

    private function period(): Period
    {
        return new Period(new RangePreset());
    }
}
