<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;
use QuickCart\ProductRecommendation\Model\Recommendation;
use QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation as RecommendationResource;

/**
 * Recommendation collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = RecommendationInterface::RECOMMENDATION_ID;

    /**
     * @var string
     */
    protected $_eventPrefix = 'quickcart_product_recommendation_collection';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Recommendation::class, RecommendationResource::class);
    }
}
