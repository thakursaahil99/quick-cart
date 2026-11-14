<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\CustomApi\Api\CategoryCatalogInterface;
use QuickCart\CustomApi\Api\Data\CategoryNodeInterface;
use QuickCart\CustomApi\Api\Data\CategoryNodeInterfaceFactory;
use QuickCart\CustomApi\Api\Data\ProductListInterface;
use QuickCart\CustomApi\Api\ProductCatalogInterface;

/**
 * Category tree (one collection query + one product-count query) and category listings.
 */
class CategoryCatalog implements CategoryCatalogInterface
{
    private const MAX_DEPTH = 5;

    /**
     * @param CollectionFactory $collectionFactory
     * @param CategoryNodeInterfaceFactory $nodeFactory
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     * @param TableMaintainer $tableMaintainer
     * @param Visibility $visibility
     * @param ProductCatalogInterface $productCatalog
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly CategoryNodeInterfaceFactory $nodeFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly ResourceConnection $resource,
        private readonly TableMaintainer $tableMaintainer,
        private readonly Visibility $visibility,
        private readonly ProductCatalogInterface $productCatalog
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getTree(int $depth = 3): array
    {
        $depth = min(self::MAX_DEPTH, max(1, $depth));
        $store = $this->storeManager->getStore();
        $storeId = (int)$store->getId();
        $rootId = (int)$store->getRootCategoryId();

        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'url_key', 'image', 'is_anchor'])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('path', ['like' => '1/' . $rootId . '/%'])
            ->addAttributeToFilter('level', ['lteq' => 1 + $depth])
            ->addUrlRewriteToResult()
            ->setOrder('level', 'ASC')
            ->setOrder('position', 'ASC');

        /** @var Category[] $categories */
        $categories = $collection->getItems();
        $counts = $this->getProductCounts(array_keys($categories), $storeId);

        $children = [];
        foreach ($categories as $category) {
            $children[(int)$category->getParentId()][] = $category;
        }

        return $this->buildNodes($rootId, $children, $counts);
    }

    /**
     * @inheritdoc
     */
    public function getProducts(
        int $categoryId,
        string $sortBy = 'position',
        string $sortDir = 'asc',
        int $page = 1,
        int $pageSize = 12
    ): ProductListInterface {
        return $this->productCatalog->getList($categoryId, null, null, null, null, $sortBy, $sortDir, $page, $pageSize);
    }

    /**
     * Nodes for the children of a parent, recursively (parents are always processed first).
     *
     * @param int $parentId
     * @param array<int, Category[]> $children
     * @param array<int, int> $counts
     * @return CategoryNodeInterface[]
     */
    private function buildNodes(int $parentId, array $children, array $counts): array
    {
        $nodes = [];
        foreach ($children[$parentId] ?? [] as $category) {
            $id = (int)$category->getId();
            $image = $category->getImageUrl();
            $nodes[] = $this->nodeFactory->create(['data' => [
                'id' => $id,
                'parent_id' => $parentId,
                'name' => (string)$category->getName(),
                'url_key' => $category->getUrlKey() ?: null,
                'url' => (string)$category->getUrl(),
                'image_url' => $image ?: null,
                'level' => (int)$category->getLevel() - 1,
                'position' => (int)$category->getPosition(),
                'product_count' => $counts[$id] ?? 0,
                'children' => $this->buildNodes($id, $children, $counts),
            ]]);
        }

        return $nodes;
    }

    /**
     * Visible products per category from the store's category product index
     * (anchor categories include their sub-categories' products).
     *
     * @param int[] $categoryIds
     * @param int $storeId
     * @return array<int, int>
     */
    private function getProductCounts(array $categoryIds, int $storeId): array
    {
        if (!$categoryIds) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                $this->tableMaintainer->getMainTable($storeId),
                ['category_id', 'products' => new Expression('COUNT(DISTINCT product_id)')]
            )
            ->where('category_id IN (?)', array_map('intval', $categoryIds))
            ->where('store_id = ?', $storeId)
            ->where('visibility IN (?)', $this->visibility->getVisibleInCatalogIds())
            ->group('category_id');

        return array_map('intval', $connection->fetchPairs($select));
    }
}
