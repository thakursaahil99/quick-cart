<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api;

use QuickCart\CustomApi\Api\Data\ProductDetailInterface;
use QuickCart\CustomApi\Api\Data\ProductListInterface;
use QuickCart\CustomApi\Api\Data\RecommendationsInterface;

/**
 * Storefront product catalog (enabled, visible products of the current store only).
 *
 * @api
 */
interface ProductCatalogInterface
{
    /**
     * Filtered, sorted and paginated product cards.
     *
     * @param int|null $categoryId Category (anchor categories include sub-categories)
     * @param string|null $brand Brand name or option ID
     * @param float|null $priceFrom Minimum final price
     * @param float|null $priceTo Maximum final price
     * @param string|null $search Text in the name or SKU
     * @param string $sortBy position, name, price or newest
     * @param string $sortDir asc or desc
     * @param int $page 1-based page
     * @param int $pageSize 1-48
     * @return \QuickCart\CustomApi\Api\Data\ProductListInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function getList(
        ?int $categoryId = null,
        ?string $brand = null,
        ?float $priceFrom = null,
        ?float $priceTo = null,
        ?string $search = null,
        string $sortBy = 'position',
        string $sortDir = 'asc',
        int $page = 1,
        int $pageSize = 12
    ): ProductListInterface;

    /**
     * Product page data by SKU.
     *
     * @param string $sku
     * @return \QuickCart\CustomApi\Api\Data\ProductDetailInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(string $sku): ProductDetailInterface;

    /**
     * "Frequently bought together" and "More like this" for a product.
     *
     * @param string $sku
     * @return \QuickCart\CustomApi\Api\Data\RecommendationsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRecommendations(string $sku): RecommendationsInterface;
}
