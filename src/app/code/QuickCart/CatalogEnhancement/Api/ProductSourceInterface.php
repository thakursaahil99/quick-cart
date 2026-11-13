<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Api;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * A merchandising source narrows and orders a storefront product collection
 * (e.g. trending, new arrivals, deals, best sellers).
 *
 * Implementations are registered in etc/di.xml on SourcePool, so new sources can
 * be added by other modules without touching the widget.
 *
 * @api
 */
interface ProductSourceInterface
{
    /**
     * Human readable label shown in the widget configuration.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Apply filters and sort order.
     *
     * The collection already has visibility, status, store and price data applied.
     *
     * @param Collection $collection
     * @return void
     */
    public function apply(Collection $collection): void;
}
