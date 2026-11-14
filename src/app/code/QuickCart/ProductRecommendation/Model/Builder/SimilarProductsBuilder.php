<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model\Builder;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\CatalogEnhancement\Model\Attributes;

/**
 * Scores catalog similarity between visible, enabled products.
 *
 * Signals: shared deepest ("leaf") category, any shared category, same brand and
 * price proximity (from the price index). Products must share at least one category.
 */
class SimilarProductsBuilder
{
    public const WEIGHT_LEAF_CATEGORY = 3.0;
    public const WEIGHT_SHARED_CATEGORY = 1.0;
    public const WEIGHT_BRAND = 1.5;
    public const WEIGHT_PRICE = 2.0;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Compute the best $maxPerProduct similar products for every visible product.
     *
     * @param int $maxPerProduct
     * @return array<int, array{product_id: int, recommended_product_id: int, score: float}>
     */
    public function build(int $maxPerProduct): array
    {
        $products = $this->loadProducts();
        if (count($products) < 2) {
            return [];
        }

        $rows = [];
        foreach ($products as $productId => $product) {
            $scores = [];
            foreach ($products as $candidateId => $candidate) {
                if ($candidateId === $productId) {
                    continue;
                }
                $score = $this->score($product, $candidate);
                if ($score > 0) {
                    $scores[$candidateId] = $score;
                }
            }
            uksort($scores, static function (int $left, int $right) use ($scores): int {
                return [$scores[$right], $left] <=> [$scores[$left], $right];
            });
            foreach (array_slice($scores, 0, max(1, $maxPerProduct), true) as $candidateId => $score) {
                $rows[] = ['product_id' => $productId, 'recommended_product_id' => $candidateId, 'score' => $score];
            }
        }

        return $rows;
    }

    /**
     * Similarity of two products; 0 when they share no category.
     *
     * @param array{leaf: int[], categories: int[], brand: int|null, price: float} $a
     * @param array{leaf: int[], categories: int[], brand: int|null, price: float} $b
     * @return float
     */
    public function score(array $a, array $b): float
    {
        if (!array_intersect($a['categories'], $b['categories'])) {
            return 0.0;
        }
        $score = self::WEIGHT_SHARED_CATEGORY;
        if (array_intersect($a['leaf'], $b['leaf'])) {
            $score += self::WEIGHT_LEAF_CATEGORY;
        }
        if ($a['brand'] !== null && $a['brand'] === $b['brand']) {
            $score += self::WEIGHT_BRAND;
        }
        $high = max($a['price'], $b['price']);
        if ($high > 0) {
            $score += self::WEIGHT_PRICE * (1 - abs($a['price'] - $b['price']) / $high);
        }

        return round($score, 4);
    }

    /**
     * Visible enabled products with their categories, brand and minimum price.
     *
     * @return array<int, array{leaf: int[], categories: int[], brand: int|null, price: float}>
     */
    private function loadProducts(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->setStoreId(Store::DEFAULT_STORE_ID)
            ->addAttributeToSelect(Attributes::BRAND)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['in' => [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]]);

        $products = [];
        foreach ($collection as $product) {
            $brand = $product->getData(Attributes::BRAND);
            $products[(int)$product->getId()] = [
                'leaf' => [],
                'categories' => [],
                'brand' => $brand === null || $brand === '' ? null : (int)$brand,
                'price' => 0.0,
            ];
        }
        if (!$products) {
            return [];
        }
        $ids = array_keys($products);
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from(['ccp' => $this->resource->getTableName('catalog_category_product')], ['product_id', 'category_id'])
            ->join(
                ['cce' => $this->resource->getTableName('catalog_category_entity')],
                'cce.entity_id = ccp.category_id',
                ['level']
            )
            ->where('ccp.product_id IN (?)', $ids)
            ->where('cce.level > ?', 1);
        $maxLevel = [];
        foreach ($connection->fetchAll($select) as $row) {
            $productId = (int)$row['product_id'];
            $level = (int)$row['level'];
            $products[$productId]['categories'][] = (int)$row['category_id'];
            if ($level > ($maxLevel[$productId] ?? 0)) {
                $maxLevel[$productId] = $level;
                $products[$productId]['leaf'] = [(int)$row['category_id']];
            } elseif ($level === $maxLevel[$productId]) {
                $products[$productId]['leaf'][] = (int)$row['category_id'];
            }
        }

        $websiteId = (int)$this->storeManager->getDefaultStoreView()?->getWebsiteId();
        $select = $connection->select()
            ->from($this->resource->getTableName('catalog_product_index_price'), ['entity_id', 'min_price'])
            ->where('entity_id IN (?)', $ids)
            ->where('customer_group_id = ?', 0)
            ->where('website_id = ?', $websiteId);
        foreach ($connection->fetchPairs($select) as $productId => $price) {
            $products[(int)$productId]['price'] = (float)$price;
        }

        return $products;
    }
}
