<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Admin grid collection: recommendations with SKU and (default store) name of both products.
 */
class Collection extends SearchResult
{
    /**
     * Main table columns that also exist on the joined product tables.
     */
    private const MAIN_COLUMNS = [
        'recommendation_id', 'product_id', 'recommended_product_id', 'type', 'source', 'score',
        'position', 'is_active', 'created_at', 'updated_at',
    ];

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $connection = $this->getConnection();
        $nameAttributeId = (int)$connection->fetchOne(
            $connection->select()
                ->from(['ea' => $this->getTable('eav_attribute')], ['attribute_id'])
                ->join(['et' => $this->getTable('eav_entity_type')], 'et.entity_type_id = ea.entity_type_id', [])
                ->where('et.entity_type_code = ?', 'catalog_product')
                ->where('ea.attribute_code = ?', 'name')
        );
        $products = $this->getTable('catalog_product_entity');
        $names = $this->getTable('catalog_product_entity_varchar');

        $this->getSelect()
            ->joinLeft(['p' => $products], 'p.entity_id = main_table.product_id', ['product_sku' => 'p.sku'])
            ->joinLeft(
                ['pn' => $names],
                'pn.entity_id = main_table.product_id AND pn.store_id = 0 AND pn.attribute_id = ' . $nameAttributeId,
                ['product_name' => 'pn.value']
            )
            ->joinLeft(
                ['r' => $products],
                'r.entity_id = main_table.recommended_product_id',
                ['recommended_sku' => 'r.sku']
            )
            ->joinLeft(
                ['rn' => $names],
                'rn.entity_id = main_table.recommended_product_id AND rn.store_id = 0 AND rn.attribute_id = '
                . $nameAttributeId,
                ['recommended_name' => 'rn.value']
            );

        foreach (self::MAIN_COLUMNS as $column) {
            $this->addFilterToMap($column, 'main_table.' . $column);
        }
        $this->addFilterToMap('product_sku', 'p.sku')
            ->addFilterToMap('product_name', 'pn.value')
            ->addFilterToMap('recommended_sku', 'r.sku')
            ->addFilterToMap('recommended_name', 'rn.value');

        return $this;
    }
}
