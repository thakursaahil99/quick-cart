<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Setup\Patch\Data;

use Magento\Catalog\Api\AttributeSetManagementInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use QuickCart\CatalogEnhancement\Model\Attributes;

/**
 * Creates the "QuickCart Electronics" attribute set (based on Default) with
 * "Specifications" and "Merchandising" groups.
 */
class CreateElectronicsAttributeSet implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeSetManagementInterface $attributeSetManagement
     * @param AttributeSetInterfaceFactory $attributeSetFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly AttributeSetManagementInterface $attributeSetManagement,
        private readonly AttributeSetInterfaceFactory $attributeSetFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = (int)$eavSetup->getEntityTypeId(Product::ENTITY);

        $setId = (int)$eavSetup->getAttributeSet($entityTypeId, Attributes::ATTRIBUTE_SET, 'attribute_set_id');
        if (!$setId) {
            $attributeSet = $this->attributeSetFactory->create();
            $attributeSet->setAttributeSetName(Attributes::ATTRIBUTE_SET);
            $attributeSet->setEntityTypeId($entityTypeId);
            $setId = (int)$this->attributeSetManagement
                ->create($attributeSet, (int)$eavSetup->getDefaultAttributeSetId($entityTypeId))
                ->getAttributeSetId();
        }

        $eavSetup->addAttributeGroup($entityTypeId, $setId, Attributes::GROUP_SPECIFICATIONS, 15);
        foreach (Attributes::SPECIFICATIONS as $sortOrder => $code) {
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $setId,
                Attributes::GROUP_SPECIFICATIONS,
                $code,
                ($sortOrder + 1) * 10
            );
        }

        $eavSetup->addAttributeGroup($entityTypeId, $setId, Attributes::GROUP_MERCHANDISING, 16);
        $eavSetup->addAttributeToGroup(
            $entityTypeId,
            $setId,
            Attributes::GROUP_MERCHANDISING,
            Attributes::IS_TRENDING,
            10
        );

        $this->moduleDataSetup->getConnection()->endSetup();

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
