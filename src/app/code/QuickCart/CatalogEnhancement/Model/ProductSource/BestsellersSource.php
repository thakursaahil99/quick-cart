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
 * Best sellers from Magento's sales report aggregation (sales_bestsellers_aggregated_yearly).
 *
 * Ordered simple products are rolled up to their visible parent (configurable,
 * bundle, grouped) through catalog_product_relation. The aggregation is refreshed by
 * Magento's report cron or Reports > Refresh Statistics; with no sales the source is
 * empty and the widget renders nothing.
 */
class BestsellersSource implements ProductSourceInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string)__('Best sellers (sales report aggregation)');
    }

    /**
     * @inheritdoc
     */
    public function apply(Collection $collection): void
    {
        $connection = $collection->getConnection();
        $productId = new Expression('COALESCE(relation.parent_id, aggregated.product_id)');

        $sales = $connection->select()
            ->from(
                ['aggregated' => $collection->getTable('sales_bestsellers_aggregated_yearly')],
                ['product_id' => $productId, 'qty_ordered' => new Expression('SUM(aggregated.qty_ordered)')]
            )
            ->joinLeft(
                ['relation' => $collection->getTable('catalog_product_relation')],
                'relation.child_id = aggregated.product_id',
                []
            )
            ->where('aggregated.store_id = ?', (int)$collection->getStoreId())
            ->group($productId);

        $collection->getSelect()
            ->join(['qc_bestsellers' => $sales], 'qc_bestsellers.product_id = e.entity_id', [])
            ->order('qc_bestsellers.qty_ordered DESC');
    }
}
