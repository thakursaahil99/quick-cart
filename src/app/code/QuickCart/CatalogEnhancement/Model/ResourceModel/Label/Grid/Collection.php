<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\ResourceModel\Label\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label as LabelResource;

/**
 * Admin grid collection of label rules with their store views.
 */
class Collection extends SearchResult
{
    /**
     * Store view filter (labels assigned to "All Store Views" always match).
     *
     * @inheritdoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'store_id') {
            $storeId = is_array($condition) ? (int)reset($condition) : (int)$condition;
            $this->getSelect()->where(
                sprintf(
                    'main_table.label_id IN (SELECT label_id FROM %s WHERE store_id IN (?))',
                    $this->getConnection()->quoteIdentifier($this->getTable(LabelResource::STORE_TABLE))
                ),
                array_unique([0, $storeId])
            );

            return $this;
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad()
    {
        $ids = array_map('intval', array_keys($this->_items));
        if ($ids) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from($this->getTable(LabelResource::STORE_TABLE), ['label_id', 'store_id'])
                ->where('label_id IN (?)', $ids);
            $stores = [];
            foreach ($connection->fetchAll($select) as $row) {
                $stores[(int)$row['label_id']][] = (int)$row['store_id'];
            }
            foreach ($this->_items as $item) {
                $item->setData('store_id', $stores[(int)$item->getId()] ?? [0]);
            }
        }

        return parent::_afterLoad();
    }
}
