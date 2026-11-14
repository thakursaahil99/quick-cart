<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;

/**
 * Label resource model; keeps quickcart_product_label_store in sync.
 */
class Label extends AbstractDb
{
    public const TABLE = 'quickcart_product_label';
    public const STORE_TABLE = 'quickcart_product_label_store';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, LabelInterface::LABEL_ID);
    }

    /**
     * Store view IDs of a label.
     *
     * @param int $labelId
     * @return int[]
     */
    public function lookupStoreIds(int $labelId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::STORE_TABLE), ['store_id'])
            ->where('label_id = ?', $labelId);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Store view IDs for many labels at once.
     *
     * @param int[] $labelIds
     * @return array<int, int[]>
     */
    public function lookupStoreIdsByLabel(array $labelIds): array
    {
        if (!$labelIds) {
            return [];
        }
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::STORE_TABLE), ['label_id', 'store_id'])
            ->where('label_id IN (?)', array_map('intval', $labelIds));
        $map = [];
        foreach ($connection->fetchAll($select) as $row) {
            $map[(int)$row['label_id']][] = (int)$row['store_id'];
        }

        return $map;
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad(AbstractModel $object)
    {
        if ($object->getId()) {
            $object->setData(LabelInterface::STORE_IDS, $this->lookupStoreIds((int)$object->getId()));
        }

        return parent::_afterLoad($object);
    }

    /**
     * @inheritdoc
     */
    protected function _afterSave(AbstractModel $object)
    {
        if ($object->hasData(LabelInterface::STORE_IDS)) {
            $this->saveStoreIds((int)$object->getId(), $object->getStoreIds());
        }

        return parent::_afterSave($object);
    }

    /**
     * Replace the store views of a label.
     *
     * @param int $labelId
     * @param int[] $storeIds
     * @return void
     */
    private function saveStoreIds(int $labelId, array $storeIds): void
    {
        $storeIds = $storeIds ?: [0];
        if (in_array(0, $storeIds, true)) {
            $storeIds = [0];
        }
        $connection = $this->getConnection();
        $table = $this->getTable(self::STORE_TABLE);
        $current = $this->lookupStoreIds($labelId);

        $delete = array_diff($current, $storeIds);
        if ($delete) {
            $connection->delete($table, ['label_id = ?' => $labelId, 'store_id IN (?)' => $delete]);
        }
        $insert = array_diff($storeIds, $current);
        if ($insert) {
            $connection->insertMultiple(
                $table,
                array_map(static fn (int $storeId) => ['label_id' => $labelId, 'store_id' => $storeId], $insert)
            );
        }
    }
}
