<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\StatusCountInterface;

/**
 * Orders and revenue for one order status.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class StatusCount extends AbstractSimpleObject implements StatusCountInterface
{
    /**
     * @inheritdoc
     */
    public function getStatus(): string
    {
        return (string)$this->_get('status');
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string)$this->_get('label');
    }

    /**
     * @inheritdoc
     */
    public function getOrderCount(): int
    {
        return (int)$this->_get('order_count');
    }

    /**
     * @inheritdoc
     */
    public function getRevenue(): float
    {
        return (float)$this->_get('revenue');
    }
}
