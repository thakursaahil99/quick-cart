<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use QuickCart\CatalogEnhancement\Api\LabelRepositoryInterface;
use QuickCart\CatalogEnhancement\Model\Label\ConditionType;
use QuickCart\CatalogEnhancement\Model\LabelFactory;

/**
 * Turns the built-in sale / new / trending badges into editable label rules
 * (Admin > QuickCart > Product Labels), using the brand palette.
 */
class InstallDefaultLabels implements DataPatchInterface
{
    private const LABELS = [
        [
            'name' => 'Sale percentage',
            'text' => '-{discount}%',
            'condition_type' => ConditionType::SALE,
            'condition_value' => '1',
            'background_color' => '#F59E0B',
            'text_color' => '#111827',
            'sort_order' => 10,
        ],
        [
            'name' => 'New arrival',
            'text' => 'New',
            'condition_type' => ConditionType::NEW,
            'condition_value' => null,
            'background_color' => '#2563EB',
            'text_color' => '#FFFFFF',
            'sort_order' => 20,
        ],
        [
            'name' => 'Trending',
            'text' => 'Trending',
            'condition_type' => ConditionType::TRENDING,
            'condition_value' => null,
            'background_color' => '#111827',
            'text_color' => '#FFFFFF',
            'sort_order' => 30,
        ],
    ];

    /**
     * @param LabelFactory $labelFactory
     * @param LabelRepositoryInterface $labelRepository
     */
    public function __construct(
        private readonly LabelFactory $labelFactory,
        private readonly LabelRepositoryInterface $labelRepository
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        foreach (self::LABELS as $data) {
            $label = $this->labelFactory->create();
            $label->setName($data['name'])
                ->setText($data['text'])
                ->setConditionType($data['condition_type'])
                ->setConditionValue($data['condition_value'])
                ->setBackgroundColor($data['background_color'])
                ->setTextColor($data['text_color'])
                ->setSortOrder($data['sort_order'])
                ->setIsActive(true)
                ->setStoreIds([0]);
            $this->labelRepository->save($label);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [CreateProductAttributes::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
