<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Api;

use QuickCart\OrderTracking\Api\Data\TrackingEventInterface;

/**
 * Tracking event persistence.
 *
 * @api
 */
interface TrackingEventRepositoryInterface
{
    /**
     * Validate and save an event.
     *
     * @param \QuickCart\OrderTracking\Api\Data\TrackingEventInterface $event
     * @return \QuickCart\OrderTracking\Api\Data\TrackingEventInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(TrackingEventInterface $event): TrackingEventInterface;

    /**
     * Load an event.
     *
     * @param int $eventId
     * @return \QuickCart\OrderTracking\Api\Data\TrackingEventInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $eventId): TrackingEventInterface;

    /**
     * Events of an order, oldest first.
     *
     * @param int $orderId
     * @param bool $customerVisibleOnly
     * @return \QuickCart\OrderTracking\Api\Data\TrackingEventInterface[]
     */
    public function getByOrderId(int $orderId, bool $customerVisibleOnly = false): array;

    /**
     * Delete an event.
     *
     * @param \QuickCart\OrderTracking\Api\Data\TrackingEventInterface $event
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(TrackingEventInterface $event): bool;
}
