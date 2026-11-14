<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Recommendations for a product.
 *
 * @api
 */
interface RecommendationsInterface
{
    /**
     * Viewed product SKU.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * "Frequently bought together" (order co-purchases, manual rows, cross-sells).
     *
     * @return \QuickCart\CustomApi\Api\Data\ProductCardInterface[]
     */
    public function getBoughtTogether(): array;

    /**
     * "More like this" (catalog similarity).
     *
     * @return \QuickCart\CustomApi\Api\Data\ProductCardInterface[]
     */
    public function getSimilar(): array;
}
