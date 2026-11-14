<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Customer account summary with order insights.
 *
 * @api
 */
interface CustomerProfileInterface
{
    /**
     * Customer ID.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * First name.
     *
     * @return string
     */
    public function getFirstname(): string;

    /**
     * Last name.
     *
     * @return string
     */
    public function getLastname(): string;

    /**
     * Email address.
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Customer group name.
     *
     * @return string
     */
    public function getGroup(): string;

    /**
     * Account created at (ISO 8601).
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Saved addresses.
     *
     * @return int
     */
    public function getAddressesCount(): int;

    /**
     * Default delivery area ("City, Region, PIN"), or null.
     *
     * @return string|null
     */
    public function getDefaultShippingArea(): ?string;

    /**
     * Non-cancelled orders.
     *
     * @return int
     */
    public function getOrderCount(): int;

    /**
     * Total spent on non-cancelled orders, net of refunds (base currency).
     *
     * @return float
     */
    public function getLifetimeValue(): float;

    /**
     * Average order value.
     *
     * @return float
     */
    public function getAverageOrderValue(): float;

    /**
     * Last order at (ISO 8601), or null.
     *
     * @return string|null
     */
    public function getLastOrderAt(): ?string;

    /**
     * Items in the wish list.
     *
     * @return int
     */
    public function getWishlistItems(): int;

    /**
     * Base currency code.
     *
     * @return string
     */
    public function getCurrency(): string;
}
