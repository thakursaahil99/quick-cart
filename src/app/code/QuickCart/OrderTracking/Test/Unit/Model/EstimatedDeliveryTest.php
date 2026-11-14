<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Test\Unit\Model;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\OrderTracking\Model\EstimatedDelivery;

#[CoversClass(EstimatedDelivery::class)]
class EstimatedDeliveryTest extends TestCase
{
    #[DataProvider('businessDayProvider')]
    public function testSundaysAreSkipped(string $placed, int $days, string $expected): void
    {
        $this->assertSame(
            $expected,
            (new EstimatedDelivery())->addBusinessDays(new DateTimeImmutable($placed), $days)->format('Y-m-d D')
        );
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function businessDayProvider(): array
    {
        return [
            'friday + 2 crosses sunday' => ['2026-11-13 17:34', 2, '2026-11-16 Mon'],
            'saturday + 1' => ['2026-11-14 09:00', 1, '2026-11-16 Mon'],
            'monday + 5' => ['2026-11-16 10:00', 5, '2026-11-21 Sat'],
            'same day' => ['2026-11-13 23:59', 0, '2026-11-13 Fri'],
        ];
    }

    public function testWindowNeverEndsBeforeItStarts(): void
    {
        $window = (new EstimatedDelivery())->window(new DateTimeImmutable('2026-11-13 10:00'), 4, 2);

        $this->assertSame('2026-11-18', $window['from']->format('Y-m-d'));
        $this->assertSame('2026-11-18', $window['to']->format('Y-m-d'));
    }
}
