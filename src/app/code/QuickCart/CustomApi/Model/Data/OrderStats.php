<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\OrderStatsInterface;

/**
 * Sales statistics for a date range (store timezone days).
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class OrderStats extends AbstractSimpleObject implements OrderStatsInterface
{
    /**
     * @inheritdoc
     */
    public function getFrom(): string
    {
        return (string)$this->_get('from');
    }

    /**
     * @inheritdoc
     */
    public function getTo(): string
    {
        return (string)$this->_get('to');
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

    /**
     * @inheritdoc
     */
    public function getAverageOrderValue(): float
    {
        return (float)$this->_get('average_order_value');
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
    public function getStatuses(): array
    {
        return (array)$this->_get('statuses');
    }
}
