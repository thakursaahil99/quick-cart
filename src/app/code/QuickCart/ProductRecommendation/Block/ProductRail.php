<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Block;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\DB\Sql\Expression;
use QuickCart\CatalogEnhancement\Block\Widget\ProductCarousel;
use QuickCart\ProductRecommendation\Model\Recommendation;

/**
 * Product rail with an explicit, ordered list of product IDs.
 *
 * Reuses the QuickCart carousel markup, cards, labels, prices and add-to-cart of the
 * merchandising widget; subclasses only decide which products to show.
 */
abstract class ProductRail extends ProductCarousel
{
    /**
     * Ordered product IDs to show.
     *
     * @return int[]
     */
    abstract public function getProductIds(): array;

    /**
     * @inheritdoc
     */
    protected function applyProductSelection(Collection $collection): bool
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->getProductIds()))));
        if (!$ids) {
            return false;
        }
        $collection->addIdFilter($ids);
        $collection->getSelect()->order(
            new Expression('FIELD(e.entity_id, ' . implode(',', $ids) . ')')
        );

        return true;
    }

    /**
     * Rails are rendered per product / per visitor, so they are not block-cached.
     *
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->unsetData('cache_lifetime');
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        return array_values(array_unique(array_merge(parent::getIdentities(), [Recommendation::CACHE_TAG])));
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        $collection = $this->getProductCollection();

        return $collection && $collection->count() ? parent::_toHtml() : '';
    }
}
