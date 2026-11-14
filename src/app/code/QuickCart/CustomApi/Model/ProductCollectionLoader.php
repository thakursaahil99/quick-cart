<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\DB\Sql\Expression;
use Magento\Review\Model\ResourceModel\Review\Summary as ReviewSummary;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\CatalogEnhancement\Model\Attributes;

/**
 * Product collections limited to what the storefront shows: enabled products of the
 * current store, visible in catalog (or search), with indexed prices, URL rewrites and
 * review summaries loaded in bulk.
 */
class ProductCollectionLoader
{
    private const ATTRIBUTES = [
        'name', 'sku', 'small_image', 'image', 'url_key', 'price', 'special_price', 'special_from_date',
        'special_to_date', 'news_from_date', 'news_to_date', Attributes::BRAND, Attributes::IS_TRENDING,
    ];

    /**
     * @param CollectionFactory $collectionFactory
     * @param Visibility $visibility
     * @param ReviewSummary $reviewSummary
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly Visibility $visibility,
        private readonly ReviewSummary $reviewSummary,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Base storefront collection (not loaded).
     *
     * @param bool $includeSearchOnly Also products visible in search only
     * @return Collection
     */
    public function create(bool $includeSearchOnly = false): Collection
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addAttributeToSelect(self::ATTRIBUTES)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->setVisibility(
                $includeSearchOnly
                    ? $this->visibility->getVisibleInSiteIds()
                    : $this->visibility->getVisibleInCatalogIds()
            )
            ->addPriceData()
            ->addUrlRewrite();

        return $collection;
    }

    /**
     * Add star-rating summaries (one join) before the collection is loaded.
     *
     * @param Collection $collection
     * @return void
     */
    public function addReviewSummary(Collection $collection): void
    {
        $this->reviewSummary->appendSummaryFieldsToCollection(
            $collection,
            (int)$this->storeManager->getStore()->getId(),
            Review::ENTITY_PRODUCT_CODE
        );
    }

    /**
     * Storefront products for IDs, in the given order, at most $limit.
     *
     * @param int[] $ids
     * @param int $limit
     * @return Product[]
     */
    public function loadByIds(array $ids, int $limit): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids || $limit < 1) {
            return [];
        }
        $collection = $this->create();
        $collection->addIdFilter($ids);
        $collection->getSelect()->order(new Expression('FIELD(e.entity_id, ' . implode(',', $ids) . ')'));
        $collection->setPageSize($limit)->setCurPage(1);
        $this->addReviewSummary($collection);

        return array_values($collection->getItems());
    }
}
