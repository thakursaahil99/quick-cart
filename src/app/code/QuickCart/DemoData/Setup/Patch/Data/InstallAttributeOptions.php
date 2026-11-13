<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Swatches\Model\Swatch;
use QuickCart\CatalogEnhancement\Model\Attributes;
use QuickCart\CatalogEnhancement\Setup\Patch\Data\CreateProductAttributes;
use QuickCart\DemoData\Model\AttributeOptionMap;
use QuickCart\DemoData\Model\Fixture;

/**
 * Adds the demo option values (brands, colours, RAM, storage, connectivity,
 * warranty) and the hex colour of every visual swatch.
 */
class InstallAttributeOptions implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Fixture $fixture
     * @param AttributeOptionMap $optionMap
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly Fixture $fixture,
        private readonly AttributeOptionMap $optionMap
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();
        $catalog = $this->fixture->getCatalog();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $lists = [
            Attributes::BRAND => $catalog['brands'],
            Attributes::COLOR => array_column($catalog['colors'], 'label'),
            Attributes::RAM => $catalog['ram'],
            Attributes::STORAGE => $catalog['storage'],
            Attributes::CONNECTIVITY => $catalog['connectivity'],
            Attributes::WARRANTY => $catalog['warranty'],
        ];
        foreach ($lists as $code => $labels) {
            $existing = $this->optionMap->get($code);
            $values = [];
            foreach (array_values($labels) as $sortOrder => $label) {
                if (!isset($existing[$label])) {
                    $values[$sortOrder] = $label;
                }
            }
            if ($values) {
                $eavSetup->addAttributeOption([
                    'attribute_id' => $this->optionMap->getAttributeId($code),
                    'values' => $values,
                ]);
            }
        }

        $colorIds = $this->optionMap->get(Attributes::COLOR);
        foreach ($catalog['colors'] as $color) {
            if (!isset($colorIds[$color['label']])) {
                continue;
            }
            $connection->insertOnDuplicate(
                $this->moduleDataSetup->getTable('eav_attribute_option_swatch'),
                [
                    'option_id' => $colorIds[$color['label']],
                    'store_id' => 0,
                    'type' => Swatch::SWATCH_TYPE_VISUAL_COLOR,
                    'value' => $color['hex'],
                ],
                ['type', 'value']
            );
        }

        $connection->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [CreateProductAttributes::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
