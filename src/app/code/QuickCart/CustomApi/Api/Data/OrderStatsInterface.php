<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Sales statistics for a date range (store timezone days).
 *
 * @api
 */
interface OrderStatsInterface
{
    /**
     * First day (Y-m-d).
     *
     * @return string
     */
    public function getFrom(): string;

    /**
     * Last day (Y-m-d).
     *
     * @return string
     */
    public function getTo(): string;

    /**
     * Orders placed (all statuses).
     *
     * @return int
     */
    public function getOrderCount(): int;

    /**
     * Revenue of non-cancelled orders, net of refunds (base currency).
     *
     * @return float
     */
    public function getRevenue(): float;

    /**
     * Average order value of non-cancelled orders.
     *
     * @return float
     */
    public function getAverageOrderValue(): float;

    /**
     * Base currency code.
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Breakdown by status.
     *
     * @return \QuickCart\CustomApi\Api\Data\StatusCountInterface[]
     */
    public function getStatuses(): array;
}
