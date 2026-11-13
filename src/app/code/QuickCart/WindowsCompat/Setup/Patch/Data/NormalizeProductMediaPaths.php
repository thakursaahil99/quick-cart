<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Repairs product image paths stored as "//q/c/x.jpg" by the core gallery handler
 * on Windows (see Model\Product\Gallery\NormalizedTmpImageMove). Rows already in
 * "/q/c/x.jpg" form - every Linux install - are untouched.
 */
class NormalizeProductMediaPaths implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $normalized = new Expression("CONCAT('/', TRIM(LEADING '/' FROM value))");

        $connection->update(
            $this->moduleDataSetup->getTable('catalog_product_entity_media_gallery'),
            ['value' => $normalized],
            ['value LIKE ?' => '//%']
        );

        $mediaAttributeIds = $connection->fetchCol(
            $connection->select()
                ->from(['attribute' => $this->moduleDataSetup->getTable('eav_attribute')], ['attribute_id'])
                ->join(
                    ['type' => $this->moduleDataSetup->getTable('eav_entity_type')],
                    'type.entity_type_id = attribute.entity_type_id',
                    []
                )
                ->where('type.entity_type_code = ?', Product::ENTITY)
                ->where('attribute.frontend_input = ?', 'media_image')
        );
        if ($mediaAttributeIds) {
            $connection->update(
                $this->moduleDataSetup->getTable('catalog_product_entity_varchar'),
                ['value' => $normalized],
                ['value LIKE ?' => '//%', 'attribute_id IN (?)' => $mediaAttributeIds]
            );
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
