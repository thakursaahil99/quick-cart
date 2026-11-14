<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Model\OrderHistory;

/**
 * Where a generated order ends up, chosen from its age so recent orders are still in
 * progress and older ones are delivered (or occasionally cancelled).
 */
class Scenario
{
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const PACKED = 'packed';
    public const SHIPPED = 'shipped';
    public const OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERED = 'delivered';
    public const CANCELED = 'canceled';

    /**
     * Hours after placing at which each milestone happens.
     */
    public const MILESTONE_HOURS = [
        'placed' => 0,
        'confirmed' => 2,
        'packed' => 20,
        'shipped' => 30,
        'out_for_delivery' => 72,
        'delivered' => 80,
        'canceled' => 5,
    ];

    /**
     * Scenario for an order placed $ageDays ago, given a random roll in [0, 1).
     *
     * @param float $ageDays
     * @param float $roll
     * @return string
     */
    public function pick(float $ageDays, float $roll): string
    {
        if ($ageDays < 0.5) {
            return $roll < 0.7 ? self::PENDING : self::CONFIRMED;
        }
        if ($ageDays < 1.5) {
            return $this->weighted($roll, [self::CONFIRMED => 0.45, self::PACKED => 0.4, self::CANCELED => 0.15]);
        }
        if ($ageDays < 3) {
            return $this->weighted($roll, [self::SHIPPED => 0.6, self::PACKED => 0.25, self::CANCELED => 0.15]);
        }
        if ($ageDays < 4) {
            return $this->weighted($roll, [self::OUT_FOR_DELIVERY => 0.55, self::SHIPPED => 0.3, self::CANCELED => 0.15]);
        }

        return $roll < 0.9 ? self::DELIVERED : self::CANCELED;
    }

    /**
     * Pick from cumulative weights (they need not sum to 1; the last bucket takes the rest).
     *
     * @param float $roll
     * @param array<string, float> $weights
     * @return string
     */
    private function weighted(float $roll, array $weights): string
    {
        $total = array_sum($weights);
        $cumulative = 0.0;
        foreach ($weights as $scenario => $weight) {
            $cumulative += $weight / $total;
            if ($roll < $cumulative) {
                return $scenario;
            }
        }

        return (string)array_key_last($weights);
    }
}
