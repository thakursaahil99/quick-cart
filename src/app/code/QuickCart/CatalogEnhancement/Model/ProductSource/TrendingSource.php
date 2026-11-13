<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\ProductSource;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use QuickCart\CatalogEnhancement\Api\ProductSourceInterface;
use QuickCart\CatalogEnhancement\Model\Attributes;

/**
 * Products flagged "Trending" by merchandisers (Merchandising > Trending).
 */
class TrendingSource implements ProductSourceInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string)__('Trending (merchandiser flag)');
    }

    /**
     * @inheritdoc
     */
    public function apply(Collection $collection): void
    {
        $collection->addAttributeToFilter(Attributes::IS_TRENDING, 1)
            ->addAttributeToSort('updated_at', Collection::SORT_ORDER_DESC);
    }
}
