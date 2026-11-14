<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Delivery tracking of an order.
 *
 * @api
 */
interface TrackingInterface
{
    /**
     * Order number.
     *
     * @return string
     */
    public function getOrderNumber(): string;

    /**
     * Placed at (ISO 8601).
     *
     * @return string
     */
    public function getPlacedAt(): string;

    /**
     * Furthest milestone reached.
     *
     * @return string
     */
    public function getMilestone(): string;

    /**
     * Milestone label.
     *
     * @return string
     */
    public function getMilestoneLabel(): string;

    /**
     * on_hold, canceled or closed when the order left the normal flow, otherwise null.
     *
     * @return string|null
     */
    public function getOffTrack(): ?string;

    /**
     * Progress 0-100.
     *
     * @return int
     */
    public function getProgress(): int;

    /**
     * Earliest estimated delivery day (Y-m-d), or null once delivered / off track.
     *
     * @return string|null
     */
    public function getEstimatedDeliveryFrom(): ?string;

    /**
     * Latest estimated delivery day (Y-m-d), or null.
     *
     * @return string|null
     */
    public function getEstimatedDeliveryTo(): ?string;

    /**
     * Delivered at (ISO 8601), or null.
     *
     * @return string|null
     */
    public function getDeliveredAt(): ?string;

    /**
     * Progress steps.
     *
     * @return \QuickCart\CustomApi\Api\Data\TrackingStepInterface[]
     */
    public function getSteps(): array;

    /**
     * Updates, newest first.
     *
     * @return \QuickCart\CustomApi\Api\Data\TrackingUpdateInterface[]
     */
    public function getUpdates(): array;

    /**
     * Carrier tracking numbers.
     *
     * @return \QuickCart\CustomApi\Api\Data\TrackingNumberInterface[]
     */
    public function getShipments(): array;
}
