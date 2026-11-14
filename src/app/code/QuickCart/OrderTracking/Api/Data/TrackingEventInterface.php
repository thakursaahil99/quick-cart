<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Api\Data;

/**
 * One entry on an order's tracking timeline.
 *
 * @api
 */
interface TrackingEventInterface
{
    public const EVENT_ID = 'event_id';
    public const ORDER_ID = 'order_id';
    public const MILESTONE = 'milestone';
    public const TITLE = 'title';
    public const COMMENT = 'comment';
    public const LOCATION = 'location';
    public const IS_VISIBLE_ON_FRONT = 'is_visible_on_front';
    public const SOURCE = 'source';
    public const REFERENCE = 'reference';
    public const CREATED_BY = 'created_by';
    public const CREATED_AT = 'created_at';

    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_ADMIN = 'admin';

    /**
     * Event ID.
     *
     * @return int|null
     */
    public function getEventId(): ?int;

    /**
     * Order entity ID.
     *
     * @return int
     */
    public function getOrderId(): int;

    /**
     * Set order entity ID.
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * Milestone code (see \QuickCart\OrderTracking\Model\Milestone).
     *
     * @return string
     */
    public function getMilestone(): string;

    /**
     * Set milestone code.
     *
     * @param string $milestone
     * @return $this
     */
    public function setMilestone(string $milestone): self;

    /**
     * Title.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Set title.
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self;

    /**
     * Comment.
     *
     * @return string|null
     */
    public function getComment(): ?string;

    /**
     * Set comment.
     *
     * @param string|null $comment
     * @return $this
     */
    public function setComment(?string $comment): self;

    /**
     * Location (city / hub).
     *
     * @return string|null
     */
    public function getLocation(): ?string;

    /**
     * Set location.
     *
     * @param string|null $location
     * @return $this
     */
    public function setLocation(?string $location): self;

    /**
     * Whether the customer sees the event.
     *
     * @return bool
     */
    public function getIsVisibleOnFront(): bool;

    /**
     * Show or hide the event for the customer.
     *
     * @param bool $visible
     * @return $this
     */
    public function setIsVisibleOnFront(bool $visible): self;

    /**
     * Source (system or admin).
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Set source.
     *
     * @param string $source
     * @return $this
     */
    public function setSource(string $source): self;

    /**
     * De-duplication reference (shipment:ID, track:ID) or null.
     *
     * @return string|null
     */
    public function getReference(): ?string;

    /**
     * Set reference.
     *
     * @param string|null $reference
     * @return $this
     */
    public function setReference(?string $reference): self;

    /**
     * Admin username for manual updates.
     *
     * @return string|null
     */
    public function getCreatedBy(): ?string;

    /**
     * Set admin username.
     *
     * @param string|null $username
     * @return $this
     */
    public function setCreatedBy(?string $username): self;

    /**
     * Creation time (UTC).
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set creation time (UTC, Y-m-d H:i:s).
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt): self;
}
