<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Swatches\Model\Swatch;
use QuickCart\CatalogEnhancement\Model\Attributes;

/**
 * Creates the electronics EAV product attributes.
 *
 * Options (brands, colours, sizes) are store data and are added by QuickCart_DemoData
 * or by merchants in Stores > Attributes > Product.
 */
class CreateProductAttributes implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->createEavSetup();

        foreach ($this->getDefinitions() as $code => $definition) {
            $eavSetup->addAttribute(Product::ENTITY, $code, $definition);
        }

        // Visual swatch: Magento_Swatches reads the input type from additional_data.
        $eavSetup->updateAttribute(
            Product::ENTITY,
            Attributes::COLOR,
            'additional_data',
            $this->serializer->serialize([
                Swatch::SWATCH_INPUT_TYPE_KEY => Swatch::SWATCH_INPUT_TYPE_VISUAL,
                'update_product_preview_image' => 1,
                'use_product_image_for_swatch' => 0,
            ])
        );

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->createEavSetup();
        foreach (array_keys($this->getDefinitions()) as $code) {
            $eavSetup->removeAttribute(Product::ENTITY, $code);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
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

    /**
     * Attribute definitions keyed by attribute code.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getDefinitions(): array
    {
        $base = [
            'global' => EavAttribute::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'apply_to' => '',
            'is_used_in_grid' => true,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => true,
            'is_html_allowed_on_front' => false,
        ];
        $selectSpec = $base + [
            'type' => 'int',
            'input' => 'select',
            'source' => Table::class,
            'searchable' => true,
            'comparable' => true,
            'visible_on_front' => true,
        ];

        return [
            Attributes::BRAND => $selectSpec + [
                'label' => 'Brand',
                'filterable' => 1,
                'filterable_in_search' => true,
                'used_in_product_listing' => true,
                'used_for_promo_rules' => true,
                'position' => 10,
            ],
            Attributes::COLOR => $selectSpec + [
                'label' => 'Color',
                'searchable' => false,
                'filterable' => 1,
                'filterable_in_search' => true,
                'used_in_product_listing' => true,
                'used_for_promo_rules' => true,
                'position' => 20,
            ],
            Attributes::RAM => $selectSpec + [
                'label' => 'RAM',
                'searchable' => false,
                'filterable' => 1,
                'filterable_in_search' => true,
                'position' => 30,
            ],
            Attributes::STORAGE => $selectSpec + [
                'label' => 'Storage',
                'searchable' => false,
                'filterable' => 1,
                'filterable_in_search' => true,
                'position' => 40,
            ],
            Attributes::CONNECTIVITY => $base + [
                'type' => 'varchar',
                'input' => 'multiselect',
                'source' => Table::class,
                'backend' => ArrayBackend::class,
                'label' => 'Connectivity',
                'searchable' => false,
                'comparable' => true,
                'visible_on_front' => true,
                'filterable' => 1,
                'filterable_in_search' => true,
                'position' => 50,
            ],
            Attributes::WARRANTY => $selectSpec + [
                'label' => 'Warranty',
                'searchable' => false,
                'filterable' => 0,
            ],
            Attributes::DISPLAY => $base + [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Display',
                'searchable' => true,
                'comparable' => true,
                'visible_on_front' => true,
            ],
            Attributes::BATTERY => $base + [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Battery',
                'searchable' => false,
                'comparable' => true,
                'visible_on_front' => true,
            ],
            Attributes::IS_TRENDING => $base + [
                'type' => 'int',
                'input' => 'boolean',
                'source' => Boolean::class,
                'label' => 'Trending',
                'default' => 0,
                'searchable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'used_for_promo_rules' => true,
            ],
        ];
    }

    /**
     * Create an EAV setup bound to this patch's setup.
     *
     * @return EavSetup
     */
    private function createEavSetup(): EavSetup
    {
        return $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
    }
}
