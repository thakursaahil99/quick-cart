<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use QuickCart\OrderTracking\Api\Data\TrackingEventInterface;
use QuickCart\OrderTracking\Api\TrackingEventRepositoryInterface;
use QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent as TrackingEventResource;

/**
 * Writes system events to the tracking timeline (used by observers and the backfill patch).
 *
 * Recording never breaks the order flow that triggered it: failures are logged.
 */
class EventRecorder
{
    /**
     * @param TrackingEventRepositoryInterface $repository
     * @param TrackingEventFactory $eventFactory
     * @param TrackingEventResource $resource
     * @param Milestone $milestone
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly TrackingEventRepositoryInterface $repository,
        private readonly TrackingEventFactory $eventFactory,
        private readonly TrackingEventResource $resource,
        private readonly Milestone $milestone,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Add an event when the order's state/status represents a new milestone.
     *
     * @param OrderInterface $order
     * @return void
     */
    public function recordStatus(OrderInterface $order): void
    {
        $orderId = (int)$order->getEntityId();
        $milestone = $this->milestone->fromOrderState((string)$order->getState(), (string)$order->getStatus());
        if (!$orderId || $milestone === null
            || !$this->shouldRecord($milestone, $this->resource->getMilestones($orderId))) {
            return;
        }
        $this->record($orderId, $milestone, '', $this->statusComment($milestone));
    }

    /**
     * Whether a status-derived milestone adds information to the timeline.
     *
     * Progress steps only move forward: Magento resets the status to "processing" when
     * a shipment or invoice is created, which must not add "Confirmed" after "Shipped".
     * Off-track milestones (hold, cancel, refund) are recorded whenever they change.
     *
     * @param string $milestone
     * @param string[] $recorded Milestones already on the timeline, oldest first
     * @return bool
     */
    public function shouldRecord(string $milestone, array $recorded): bool
    {
        $step = $this->milestone->stepIndex($milestone);
        if ($step < 0) {
            return end($recorded) !== $milestone;
        }
        $reached = -1;
        foreach ($recorded as $previous) {
            $reached = max($reached, $this->milestone->stepIndex($previous));
        }

        return $step > $reached;
    }

    /**
     * Add an event once per reference (e.g. "shipment:12").
     *
     * @param int $orderId
     * @param string $milestone
     * @param string $title
     * @param string|null $comment
     * @param string|null $reference
     * @param string|null $createdAt UTC time; defaults to now
     * @return void
     */
    public function record(
        int $orderId,
        string $milestone,
        string $title = '',
        ?string $comment = null,
        ?string $reference = null,
        ?string $createdAt = null
    ): void {
        if ($reference !== null && $this->resource->hasReference($orderId, $reference)) {
            return;
        }
        try {
            $event = $this->eventFactory->create();
            $event->setOrderId($orderId)
                ->setMilestone($milestone)
                ->setTitle($title)
                ->setComment($comment)
                ->setIsVisibleOnFront(true)
                ->setSource(TrackingEventInterface::SOURCE_SYSTEM)
                ->setReference($reference);
            if ($createdAt !== null) {
                $event->setCreatedAt($createdAt);
            }
            $this->repository->save($event);
        } catch (\Exception $e) {
            $this->logger->error('QuickCart tracking event not recorded: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'milestone' => $milestone,
            ]);
        }
    }

    /**
     * Customer-facing explanation for automatic status events.
     *
     * @param string $milestone
     * @return string|null
     */
    private function statusComment(string $milestone): ?string
    {
        $comments = [
            Milestone::PLACED => __('We have received your order.'),
            Milestone::CONFIRMED => __('Your order is confirmed and being prepared.'),
            Milestone::PACKED => __('Your items are packed and ready for pickup.'),
            Milestone::SHIPPED => __('Your package is on its way.'),
            Milestone::OUT_FOR_DELIVERY => __('Your package is out for delivery today.'),
            Milestone::DELIVERED => __('Your order has been delivered.'),
            Milestone::ON_HOLD => __('Your order is on hold. We will contact you shortly.'),
            Milestone::CANCELED => __('Your order has been cancelled.'),
            Milestone::CLOSED => __('Your order has been refunded.'),
        ];

        return isset($comments[$milestone]) ? (string)$comments[$milestone] : null;
    }
}
