<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\RecommendationsInterface;

/**
 * Recommendations for a product.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class Recommendations extends AbstractSimpleObject implements RecommendationsInterface
{
    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return (string)$this->_get('sku');
    }

    /**
     * @inheritdoc
     */
    public function getBoughtTogether(): array
    {
        return (array)$this->_get('bought_together');
    }

    /**
     * @inheritdoc
     */
    public function getSimilar(): array
    {
        return (array)$this->_get('similar');
    }
}
