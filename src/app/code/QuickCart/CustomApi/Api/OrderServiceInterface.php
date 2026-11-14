<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api;

use QuickCart\CustomApi\Api\Data\OrderListInterface;
use QuickCart\CustomApi\Api\Data\OrderStatsInterface;
use QuickCart\CustomApi\Api\Data\TrackingInterface;

/**
 * Orders: customer history and tracking, guest tracking, admin statistics and updates.
 *
 * @api
 */
interface OrderServiceInterface
{
    /**
     * The customer's orders, newest first.
     *
     * @param int $customerId
     * @param int $page
     * @param int $pageSize 1-50
     * @return \QuickCart\CustomApi\Api\Data\OrderListInterface
     */
    public function getCustomerOrders(int $customerId, int $page = 1, int $pageSize = 10): OrderListInterface;

    /**
     * Tracking of one of the customer's own orders.
     *
     * @param int $customerId
     * @param string $orderNumber
     * @return \QuickCart\CustomApi\Api\Data\TrackingInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerOrderTracking(int $customerId, string $orderNumber): TrackingInterface;

    /**
     * Guest tracking: order number plus the email address or mobile number used at checkout.
     *
     * @param string $orderNumber
     * @param string $contact
     * @return \QuickCart\CustomApi\Api\Data\TrackingInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Webapi\Exception Too many failed lookups (HTTP 429)
     */
    public function trackGuestOrder(string $orderNumber, string $contact): TrackingInterface;

    /**
     * Sales statistics for a date range (defaults to the last 30 days).
     *
     * @param string|null $from First day, Y-m-d
     * @param string|null $to Last day, Y-m-d
     * @return \QuickCart\CustomApi\Api\Data\OrderStatsInterface
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getStats(?string $from = null, ?string $to = null): OrderStatsInterface;

    /**
     * Add a tracking update to an order; optionally move it to the matching status.
     *
     * @param int $orderId Order entity ID
     * @param string $milestone placed, confirmed, packed, shipped, out_for_delivery, delivered, on_hold, update
     * @param string|null $title Defaults to the milestone label
     * @param string|null $comment Message to the customer
     * @param string|null $location
     * @param bool $visibleToCustomer
     * @param bool $updateStatus Also set the order status (confirmed, packed, shipped, out_for_delivery)
     * @return \QuickCart\CustomApi\Api\Data\TrackingInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function addTrackingUpdate(
        int $orderId,
        string $milestone,
        ?string $title = null,
        ?string $comment = null,
        ?string $location = null,
        bool $visibleToCustomer = true,
        bool $updateStatus = false
    ): TrackingInterface;
}
