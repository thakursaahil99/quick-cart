<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;

/**
 * Where a recommendation came from.
 */
class Origin implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => RecommendationInterface::SOURCE_MANUAL, 'label' => __('Manual (admin)')],
            ['value' => RecommendationInterface::SOURCE_ORDERS, 'label' => __('Order history')],
            ['value' => RecommendationInterface::SOURCE_CATALOG, 'label' => __('Catalog similarity')],
        ];
    }
}
