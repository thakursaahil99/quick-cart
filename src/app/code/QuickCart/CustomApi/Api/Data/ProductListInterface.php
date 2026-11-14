<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * A page of product cards.
 *
 * @api
 */
interface ProductListInterface
{
    /**
     * Products on this page.
     *
     * @return \QuickCart\CustomApi\Api\Data\ProductCardInterface[]
     */
    public function getItems(): array;

    /**
     * Matching products across all pages.
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

    /**
     * Number of pages.
     *
     * @return int
     */
    public function getTotalPages(): int;
}
