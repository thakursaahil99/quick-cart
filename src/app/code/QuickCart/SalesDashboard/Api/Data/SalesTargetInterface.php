<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Api\Data;

/**
 * Monthly sales target.
 *
 * @api
 */
interface SalesTargetInterface
{
    public const TARGET_ID = 'target_id';
    public const PERIOD = 'period';
    public const REVENUE_TARGET = 'revenue_target';
    public const ORDERS_TARGET = 'orders_target';
    public const NOTE = 'note';

    /**
     * Target ID.
     *
     * @return int|null
     */
    public function getTargetId(): ?int;

    /**
     * Month, YYYY-MM.
     *
     * @return string
     */
    public function getPeriod(): string;

    /**
     * Set month (YYYY-MM).
     *
     * @param string $period
     * @return $this
     */
    public function setPeriod(string $period): self;

    /**
     * Revenue target in the base currency.
     *
     * @return float
     */
    public function getRevenueTarget(): float;

    /**
     * Set revenue target.
     *
     * @param float $amount
     * @return $this
     */
    public function setRevenueTarget(float $amount): self;

    /**
     * Orders target.
     *
     * @return int
     */
    public function getOrdersTarget(): int;

    /**
     * Set orders target.
     *
     * @param int $orders
     * @return $this
     */
    public function setOrdersTarget(int $orders): self;

    /**
     * Note, or null.
     *
     * @return string|null
     */
    public function getNote(): ?string;

    /**
     * Set note.
     *
     * @param string|null $note
     * @return $this
     */
    public function setNote(?string $note): self;
}
