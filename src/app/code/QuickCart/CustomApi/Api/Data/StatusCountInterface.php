<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Orders and revenue for one order status.
 *
 * @api
 */
interface StatusCountInterface
{
    /**
     * Status code.
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Status label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Orders.
     *
     * @return int
     */
    public function getOrderCount(): int;

    /**
     * Grand total in the base currency.
     *
     * @return float
     */
    public function getRevenue(): float;
}
