<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api;

use QuickCart\CustomApi\Api\Data\ProductListInterface;

/**
 * Storefront category tree and category product listings.
 *
 * @api
 */
interface CategoryCatalogInterface
{
    /**
     * Active categories below the store's root category.
     *
     * @param int $depth Levels to return (1-5)
     * @return \QuickCart\CustomApi\Api\Data\CategoryNodeInterface[]
     */
    public function getTree(int $depth = 3): array;

    /**
     * Products of an active category of this store.
     *
     * @param int $categoryId
     * @param string $sortBy position, name, price or newest
     * @param string $sortDir asc or desc
     * @param int $page 1-based page
     * @param int $pageSize 1-48
     * @return \QuickCart\CustomApi\Api\Data\ProductListInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getProducts(
        int $categoryId,
        string $sortBy = 'position',
        string $sortDir = 'asc',
        int $page = 1,
        int $pageSize = 12
    ): ProductListInterface;
}
