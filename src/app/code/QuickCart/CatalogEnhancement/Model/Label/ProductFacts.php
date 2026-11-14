<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\Label;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use QuickCart\CatalogEnhancement\Model\Attributes;

/**
 * Catalog facts the label conditions are evaluated against.
 *
 * Category assignments are read with one query per product list when preload() is
 * called (product carousels and the category product list do), otherwise lazily per
 * product. Category trees are expanded once per request.
 */
class ProductFacts
{
    /**
     * @var array<int, int[]>
     */
    private array $categoryIds = [];

    /**
     * @var array<string, int[]>
     */
    private array $expandedCategories = [];

    /**
     * @var string|null
     */
    private ?string $today = null;

    /**
     * @param TimezoneInterface $timezone
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly TimezoneInterface $timezone,
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Today in the store timezone (Y-m-d).
     *
     * @return string
     */
    public function getToday(): string
    {
        return $this->today ??= $this->timezone->date()->format('Y-m-d');
    }

    /**
     * Whole-percent discount of the final price versus the regular price.
     *
     * For configurable products both prices are the minimum across children.
     *
     * @param Product $product
     * @return int
     */
    public function getDiscountPercent(Product $product): int
    {
        $priceInfo = $product->getPriceInfo();
        $regular = (float)$priceInfo->getPrice(RegularPrice::PRICE_CODE)->getAmount()->getValue();
        $final = (float)$priceInfo->getPrice(FinalPrice::PRICE_CODE)->getAmount()->getValue();
        if ($regular <= 0.0 || $final >= $regular) {
            return 0;
        }

        return (int)floor(($regular - $final) / $regular * 100);
    }

    /**
     * Whether today falls inside the product's "Set Product as New" range.
     *
     * @param Product $product
     * @return bool
     */
    public function isNew(Product $product): bool
    {
        $from = (string)$product->getData('news_from_date');
        $to = (string)$product->getData('news_to_date');
        if ($from === '' && $to === '') {
            return false;
        }
        $today = $this->getToday();

        return ($from === '' || substr($from, 0, 10) <= $today)
            && ($to === '' || substr($to, 0, 10) >= $today);
    }

    /**
     * Whether the product carries the Trending flag.
     *
     * @param Product $product
     * @return bool
     */
    public function isTrending(Product $product): bool
    {
        return (int)$product->getData(Attributes::IS_TRENDING) === 1;
    }

    /**
     * Load the category assignments of many products in one query.
     *
     * @param int[] $productIds
     * @return void
     */
    public function preloadCategoryIds(array $productIds): void
    {
        $productIds = array_values(array_diff(array_map('intval', $productIds), array_keys($this->categoryIds)));
        if (!$productIds) {
            return;
        }
        foreach ($productIds as $productId) {
            $this->categoryIds[$productId] = [];
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('catalog_category_product'), ['product_id', 'category_id'])
            ->where('product_id IN (?)', $productIds);
        foreach ($connection->fetchAll($select) as $row) {
            $this->categoryIds[(int)$row['product_id']][] = (int)$row['category_id'];
        }
    }

    /**
     * Categories the product is assigned to.
     *
     * @param Product $product
     * @return int[]
     */
    public function getCategoryIds(Product $product): array
    {
        $productId = (int)$product->getId();
        if (!isset($this->categoryIds[$productId])) {
            $this->preloadCategoryIds([$productId]);
        }

        return $this->categoryIds[$productId];
    }

    /**
     * The given categories plus all their descendants.
     *
     * @param int[] $categoryIds
     * @return int[]
     */
    public function expandCategoryIds(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        sort($categoryIds);
        $key = implode(',', $categoryIds);
        if ($key === '') {
            return [];
        }
        if (!isset($this->expandedCategories[$key])) {
            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->from($this->resource->getTableName('catalog_category_entity'), ['entity_id'])
                ->where('entity_id IN (?)', $categoryIds);
            foreach ($categoryIds as $categoryId) {
                $select->orWhere('path LIKE ?', '%/' . $categoryId . '/%');
            }
            $this->expandedCategories[$key] = array_map('intval', $connection->fetchCol($select));
        }

        return $this->expandedCategories[$key];
    }
}
