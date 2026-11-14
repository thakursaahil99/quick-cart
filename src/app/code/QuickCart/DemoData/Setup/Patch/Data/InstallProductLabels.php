<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use QuickCart\CatalogEnhancement\Api\LabelRepositoryInterface;
use QuickCart\CatalogEnhancement\Model\Label\ConditionType;
use QuickCart\CatalogEnhancement\Model\LabelFactory;
use QuickCart\CatalogEnhancement\Setup\Patch\Data\InstallDefaultLabels;

/**
 * Demo label rules on top of the default sale / new / trending labels:
 * an SKU-based "Editor's pick" and a date-limited "Gaming Week" category campaign.
 */
class InstallProductLabels implements DataPatchInterface
{
    private const EDITORS_PICK_SKUS = ['QC-PH-EDGE5PRO', 'QC-LT-PRO16', 'QC-HP-AURA', 'QC-CM-ALPHAM50'];
    private const CAMPAIGN_CATEGORY = 'gaming';
    private const CAMPAIGN_DAYS = 45;

    /**
     * @param LabelFactory $labelFactory
     * @param LabelRepositoryInterface $labelRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly LabelFactory $labelFactory,
        private readonly LabelRepositoryInterface $labelRepository,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $label = $this->labelFactory->create();
        $label->setName("Editor's pick (flagships)")
            ->setText("Editor's pick")
            ->setConditionType(ConditionType::SKU)
            ->setConditionValue(implode(', ', self::EDITORS_PICK_SKUS))
            ->setBackgroundColor('#059669')
            ->setTextColor('#FFFFFF')
            ->setSortOrder(15)
            ->setIsActive(true)
            ->setStoreIds([0]);
        $this->labelRepository->save($label);

        $categoryId = (int)$this->categoryCollectionFactory->create()
            ->addAttributeToFilter('url_key', self::CAMPAIGN_CATEGORY)
            ->setPageSize(1)
            ->getFirstItem()
            ->getId();
        if ($categoryId) {
            $today = $this->timezone->date();
            $label = $this->labelFactory->create();
            $label->setName('Gaming Week campaign')
                ->setText('Gaming Week')
                ->setConditionType(ConditionType::CATEGORY)
                ->setConditionValue((string)$categoryId)
                ->setBackgroundColor('#7C3AED')
                ->setTextColor('#FFFFFF')
                ->setSortOrder(25)
                ->setIsActive(true)
                ->setFromDate($today->format('Y-m-d'))
                ->setToDate($today->modify('+' . self::CAMPAIGN_DAYS . ' days')->format('Y-m-d'))
                ->setStoreIds([0]);
            $this->labelRepository->save($label);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [InstallDefaultLabels::class, InstallCategories::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
