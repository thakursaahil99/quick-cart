<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\OrderSummaryInterface;

/**
 * Order row for "My Orders".
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class OrderSummary extends AbstractSimpleObject implements OrderSummaryInterface
{
    /**
     * @inheritdoc
     */
    public function getOrderNumber(): string
    {
        return (string)$this->_get('order_number');
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->_get('created_at');
    }

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
    public function getStatusLabel(): string
    {
        return (string)$this->_get('status_label');
    }

    /**
     * @inheritdoc
     */
    public function getMilestone(): string
    {
        return (string)$this->_get('milestone');
    }

    /**
     * @inheritdoc
     */
    public function getMilestoneLabel(): string
    {
        return (string)$this->_get('milestone_label');
    }

    /**
     * @inheritdoc
     */
    public function getGrandTotal(): float
    {
        return (float)$this->_get('grand_total');
    }

    /**
     * @inheritdoc
     */
    public function getCurrency(): string
    {
        return (string)$this->_get('currency');
    }

    /**
     * @inheritdoc
     */
    public function getItemsCount(): int
    {
        return (int)$this->_get('items_count');
    }
}
