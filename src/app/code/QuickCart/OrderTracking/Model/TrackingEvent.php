<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use Magento\Framework\Model\AbstractModel;
use QuickCart\OrderTracking\Api\Data\TrackingEventInterface;
use QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent as TrackingEventResource;

/**
 * Tracking timeline entry.
 */
class TrackingEvent extends AbstractModel implements TrackingEventInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'quickcart_order_tracking_event';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(TrackingEventResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getEventId(): ?int
    {
        $id = $this->getData(self::EVENT_ID);

        return $id === null ? null : (int)$id;
    }

    /**
     * @inheritdoc
     */
    public function getOrderId(): int
    {
        return (int)$this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId(int $orderId): TrackingEventInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getMilestone(): string
    {
        return (string)$this->getData(self::MILESTONE);
    }

    /**
     * @inheritdoc
     */
    public function setMilestone(string $milestone): TrackingEventInterface
    {
        return $this->setData(self::MILESTONE, $milestone);
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return (string)$this->getData(self::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setTitle(string $title): TrackingEventInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getComment(): ?string
    {
        $value = $this->getData(self::COMMENT);

        return $value === null || $value === '' ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function setComment(?string $comment): TrackingEventInterface
    {
        return $this->setData(self::COMMENT, $comment);
    }

    /**
     * @inheritdoc
     */
    public function getLocation(): ?string
    {
        $value = $this->getData(self::LOCATION);

        return $value === null || $value === '' ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function setLocation(?string $location): TrackingEventInterface
    {
        return $this->setData(self::LOCATION, $location);
    }

    /**
     * @inheritdoc
     */
    public function getIsVisibleOnFront(): bool
    {
        return (bool)$this->getData(self::IS_VISIBLE_ON_FRONT);
    }

    /**
     * @inheritdoc
     */
    public function setIsVisibleOnFront(bool $visible): TrackingEventInterface
    {
        return $this->setData(self::IS_VISIBLE_ON_FRONT, (int)$visible);
    }

    /**
     * @inheritdoc
     */
    public function getSource(): string
    {
        return (string)($this->getData(self::SOURCE) ?: self::SOURCE_SYSTEM);
    }

    /**
     * @inheritdoc
     */
    public function setSource(string $source): TrackingEventInterface
    {
        return $this->setData(self::SOURCE, $source);
    }

    /**
     * @inheritdoc
     */
    public function getReference(): ?string
    {
        $value = $this->getData(self::REFERENCE);

        return $value === null || $value === '' ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function setReference(?string $reference): TrackingEventInterface
    {
        return $this->setData(self::REFERENCE, $reference);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedBy(): ?string
    {
        $value = $this->getData(self::CREATED_BY);

        return $value === null || $value === '' ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedBy(?string $username): TrackingEventInterface
    {
        return $this->setData(self::CREATED_BY, $username);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt): TrackingEventInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
