<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Model;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;

/**
 * Resolves admin-scope option labels to option IDs for a product attribute.
 *
 * Reads the EAV option tables directly so freshly created options are visible
 * within the same setup:upgrade run (attribute source models cache their options).
 */
class AttributeOptionMap
{
    /**
     * @param ResourceConnection $resource
     * @param EavConfig $eavConfig
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig
    ) {
    }

    /**
     * Label => option ID map.
     *
     * @param string $attributeCode
     * @return array<string, int>
     */
    public function get(string $attributeCode): array
    {
        $attributeId = (int)$this->eavConfig->getAttribute(Product::ENTITY, $attributeCode)->getId();
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['option' => $this->resource->getTableName('eav_attribute_option')], ['option_id'])
            ->join(
                ['label' => $this->resource->getTableName('eav_attribute_option_value')],
                'label.option_id = option.option_id AND label.store_id = 0',
                ['value']
            )
            ->where('option.attribute_id = ?', $attributeId);

        $map = [];
        foreach ($connection->fetchAll($select) as $row) {
            $map[(string)$row['value']] = (int)$row['option_id'];
        }

        return $map;
    }

    /**
     * Attribute ID for an attribute code.
     *
     * @param string $attributeCode
     * @return int
     */
    public function getAttributeId(string $attributeCode): int
    {
        return (int)$this->eavConfig->getAttribute(Product::ENTITY, $attributeCode)->getId();
    }
}
