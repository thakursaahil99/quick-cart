<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\ProductSource;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\DB\Sql\Expression;
use QuickCart\CatalogEnhancement\Api\ProductSourceInterface;

/**
 * Products currently selling below their regular price (special price, catalog
 * price rules, tier prices for the customer group), biggest discount first.
 *
 * Reads the price index joined by addFinalPrice(), so it honours every Magento
 * pricing mechanism without recalculating prices in PHP.
 */
class DealsSource implements ProductSourceInterface
{
    private const PRICE_INDEX_ALIAS = 'price_index';

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string)__('Deals (final price below regular price)');
    }

    /**
     * @inheritdoc
     */
    public function apply(Collection $collection): void
    {
        $alias = self::PRICE_INDEX_ALIAS;
        $collection->getSelect()
            ->where("{$alias}.final_price < {$alias}.price")
            ->order(new Expression("({$alias}.price - {$alias}.final_price) / {$alias}.price DESC"));
    }
}
