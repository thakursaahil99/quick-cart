<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use DateTimeImmutable;

/**
 * Estimated delivery window in business days (Sundays are skipped).
 */
class EstimatedDelivery
{
    /**
     * Earliest and latest delivery day for an order placed at $placedAt.
     *
     * @param DateTimeImmutable $placedAt Order time in the store timezone
     * @param int $minDays
     * @param int $maxDays
     * @return array{from: DateTimeImmutable, to: DateTimeImmutable}
     */
    public function window(DateTimeImmutable $placedAt, int $minDays, int $maxDays): array
    {
        $minDays = max(0, $minDays);
        $maxDays = max($minDays, $maxDays);

        return [
            'from' => $this->addBusinessDays($placedAt, $minDays),
            'to' => $this->addBusinessDays($placedAt, $maxDays),
        ];
    }

    /**
     * Add business days (Monday-Saturday) to a date.
     *
     * @param DateTimeImmutable $date
     * @param int $days
     * @return DateTimeImmutable
     */
    public function addBusinessDays(DateTimeImmutable $date, int $days): DateTimeImmutable
    {
        $result = $date->setTime(0, 0);
        while ($days > 0) {
            $result = $result->modify('+1 day');
            if ((int)$result->format('N') !== 7) {
                $days--;
            }
        }

        return $result;
    }
}
