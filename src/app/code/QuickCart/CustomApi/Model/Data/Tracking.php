<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\TrackingInterface;

/**
 * Delivery tracking of an order.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class Tracking extends AbstractSimpleObject implements TrackingInterface
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
    public function getPlacedAt(): string
    {
        return (string)$this->_get('placed_at');
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
    public function getOffTrack(): ?string
    {
        $value = $this->_get('off_track');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getProgress(): int
    {
        return (int)$this->_get('progress');
    }

    /**
     * @inheritdoc
     */
    public function getEstimatedDeliveryFrom(): ?string
    {
        $value = $this->_get('estimated_delivery_from');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getEstimatedDeliveryTo(): ?string
    {
        $value = $this->_get('estimated_delivery_to');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getDeliveredAt(): ?string
    {
        $value = $this->_get('delivered_at');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getSteps(): array
    {
        return (array)$this->_get('steps');
    }

    /**
     * @inheritdoc
     */
    public function getUpdates(): array
    {
        return (array)$this->_get('updates');
    }

    /**
     * @inheritdoc
     */
    public function getShipments(): array
    {
        return (array)$this->_get('shipments');
    }
}
