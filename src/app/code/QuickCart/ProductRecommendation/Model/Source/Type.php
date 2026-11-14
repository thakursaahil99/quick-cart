<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;

/**
 * Recommendation types.
 */
class Type implements OptionSourceInterface
{
    /**
     * Type => label.
     *
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return [
            RecommendationInterface::TYPE_BOUGHT_TOGETHER => (string)__('Frequently bought together'),
            RecommendationInterface::TYPE_SIMILAR => (string)__('Similar products'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
