<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Order row for "My Orders".
 *
 * @api
 */
interface OrderSummaryInterface
{
    /**
     * Order number (increment ID).
     *
     * @return string
     */
    public function getOrderNumber(): string;

    /**
     * Placed at (ISO 8601).
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Magento order status code.
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Storefront status label.
     *
     * @return string
     */
    public function getStatusLabel(): string;

    /**
     * Tracking milestone (placed, confirmed, packed, shipped, out_for_delivery, delivered, ...).
     *
     * @return string
     */
    public function getMilestone(): string;

    /**
     * Milestone label.
     *
     * @return string
     */
    public function getMilestoneLabel(): string;

    /**
     * Grand total in the order currency.
     *
     * @return float
     */
    public function getGrandTotal(): float;

    /**
     * Order currency code.
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Number of ordered items (visible lines).
     *
     * @return int
     */
    public function getItemsCount(): int;
}
