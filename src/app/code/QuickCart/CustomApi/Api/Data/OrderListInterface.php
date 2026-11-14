<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * A page of the customer's orders, newest first.
 *
 * @api
 */
interface OrderListInterface
{
    /**
     * Orders on this page.
     *
     * @return \QuickCart\CustomApi\Api\Data\OrderSummaryInterface[]
     */
    public function getItems(): array;

    /**
     * All orders of the customer.
     *
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * Current page (1-based).
     *
     * @return int
     */
    public function getPage(): int;

    /**
     * Page size.
     *
     * @return int
     */
    public function getPageSize(): int;
}
