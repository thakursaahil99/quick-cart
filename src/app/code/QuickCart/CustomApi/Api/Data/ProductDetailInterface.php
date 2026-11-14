<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Product page data.
 *
 * @api
 */
interface ProductDetailInterface
{
    /**
     * Card data (prices, labels, rating, stock).
     *
     * @return \QuickCart\CustomApi\Api\Data\ProductCardInterface
     */
    public function getProduct(): ProductCardInterface;

    /**
     * Short description (HTML), or null.
     *
     * @return string|null
     */
    public function getShortDescription(): ?string;

    /**
     * Description (HTML), or null.
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Specifications with values.
     *
     * @return \QuickCart\CustomApi\Api\Data\AttributeValueInterface[]
     */
    public function getSpecifications(): array;

    /**
     * Gallery image URLs in position order.
     *
     * @return string[]
     */
    public function getImages(): array;

    /**
     * Configurable options.
     *
     * @return \QuickCart\CustomApi\Api\Data\ProductOptionInterface[]
     */
    public function getOptions(): array;

    /**
     * Category IDs.
     *
     * @return int[]
     */
    public function getCategoryIds(): array;

    /**
     * Related product SKUs.
     *
     * @return string[]
     */
    public function getRelatedSkus(): array;

    /**
     * Up-sell product SKUs.
     *
     * @return string[]
     */
    public function getUpsellSkus(): array;
}
