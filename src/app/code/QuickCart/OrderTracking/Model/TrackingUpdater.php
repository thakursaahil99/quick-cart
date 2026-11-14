<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use QuickCart\OrderTracking\Api\Data\TrackingEventInterface;
use QuickCart\OrderTracking\Api\TrackingEventRepositoryInterface;

/**
 * Adds a manual tracking update to an order (admin order view and REST API) and can move
 * a new / processing order to the matching status (Confirmed, Packed, Shipped, Out for
 * Delivery).
 */
class TrackingUpdater
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param TrackingEventRepositoryInterface $eventRepository
     * @param TrackingEventFactory $eventFactory
     * @param Milestone $milestone
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TrackingEventRepositoryInterface $eventRepository,
        private readonly TrackingEventFactory $eventFactory,
        private readonly Milestone $milestone
    ) {
    }

    /**
     * Record the update; optionally change the order status.
     *
     * @param Order $order
     * @param string $milestone
     * @param string $title Empty: the milestone label
     * @param string|null $comment
     * @param string|null $location
     * @param bool $visibleOnFront
     * @param bool $updateStatus
     * @param string|null $createdBy
     * @return TrackingEventInterface
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function addUpdate(
        Order $order,
        string $milestone,
        string $title = '',
        ?string $comment = null,
        ?string $location = null,
        bool $visibleOnFront = true,
        bool $updateStatus = false,
        ?string $createdBy = null
    ): TrackingEventInterface {
        if ($updateStatus) {
            $this->assertStatusChangeAllowed($order, $milestone);
        }
        $event = $this->eventFactory->create();
        $event->setOrderId((int)$order->getEntityId())
            ->setMilestone($milestone)
            ->setTitle($title)
            ->setComment($comment ?: null)
            ->setLocation($location ?: null)
            ->setIsVisibleOnFront($visibleOnFront)
            ->setSource(TrackingEventInterface::SOURCE_ADMIN)
            ->setCreatedBy($createdBy);
        // Saved before the order so the status observer sees this milestone as recorded.
        $this->eventRepository->save($event);

        if ($updateStatus) {
            $order->setState(Order::STATE_PROCESSING)
                ->setStatus(Milestone::STATUS_FOR_MILESTONE[$milestone]);
            $order->addCommentToStatusHistory((string)__('Tracking: %1', $event->getTitle()), false, true)
                ->setIsCustomerNotified(false);
            $this->orderRepository->save($order);
        }

        return $event;
    }

    /**
     * Milestones that can also set the order status, for orders in this state.
     *
     * @param Order $order
     * @return string[]
     */
    public function getStatusMilestones(Order $order): array
    {
        return in_array($order->getState(), [Order::STATE_NEW, Order::STATE_PROCESSING], true)
            ? array_keys(Milestone::STATUS_FOR_MILESTONE)
            : [];
    }

    /**
     * Reject status changes that Magento's order state machine does not allow.
     *
     * @param Order $order
     * @param string $milestone
     * @return void
     * @throws LocalizedException
     */
    private function assertStatusChangeAllowed(Order $order, string $milestone): void
    {
        if (!isset(Milestone::STATUS_FOR_MILESTONE[$milestone])) {
            throw new LocalizedException(__(
                '"%1" does not map to an order status. Add the update without changing the status.',
                $this->milestone->getLabel($milestone)
            ));
        }
        if (!in_array($milestone, $this->getStatusMilestones($order), true)) {
            throw new LocalizedException(__(
                'The status of a %1 order cannot change to "%2".',
                $order->getState(),
                $this->milestone->getLabel($milestone)
            ));
        }
    }
}
