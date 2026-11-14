<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\ResourceModel\Label;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;
use QuickCart\CatalogEnhancement\Model\Label;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label as LabelResource;

/**
 * Label rule collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = LabelInterface::LABEL_ID;

    /**
     * @var string
     */
    protected $_eventPrefix = 'quickcart_product_label_collection';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Label::class, LabelResource::class);
    }

    /**
     * Only labels assigned to the store view or to all store views.
     *
     * @param int $storeId
     * @return $this
     */
    public function addStoreFilter(int $storeId): self
    {
        $storeTable = $this->getTable(LabelResource::STORE_TABLE);
        $this->getSelect()->where(
            sprintf(
                'main_table.label_id IN (SELECT label_id FROM %s WHERE store_id IN (?))',
                $this->getConnection()->quoteIdentifier($storeTable)
            ),
            [0, $storeId]
        );

        return $this;
    }

    /**
     * Enabled labels whose date range includes the given day (Y-m-d).
     *
     * @param string $date
     * @return $this
     */
    public function addActiveFilter(string $date): self
    {
        $this->addFieldToFilter(LabelInterface::IS_ACTIVE, 1);
        $this->getSelect()
            ->where('main_table.from_date IS NULL OR main_table.from_date <= ?', $date)
            ->where('main_table.to_date IS NULL OR main_table.to_date >= ?', $date);

        return $this;
    }

    /**
     * Priority order (sort_order, then ID).
     *
     * @return $this
     */
    public function addPriorityOrder(): self
    {
        $this->setOrder(LabelInterface::SORT_ORDER, self::SORT_ORDER_ASC);
        $this->setOrder(LabelInterface::LABEL_ID, self::SORT_ORDER_ASC);

        return $this;
    }

    /**
     * Attach store IDs to every loaded label in one query.
     *
     * @inheritdoc
     */
    protected function _afterLoad()
    {
        /** @var LabelResource $resource */
        $resource = $this->getResource();
        $storeIds = $resource->lookupStoreIdsByLabel(array_map('intval', array_keys($this->_items)));
        foreach ($this->_items as $item) {
            $item->setData(LabelInterface::STORE_IDS, $storeIds[(int)$item->getId()] ?? [0]);
        }

        return parent::_afterLoad();
    }
}
