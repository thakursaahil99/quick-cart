<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use Magento\Sales\Model\Order;
use QuickCart\CustomApi\Api\Data\TrackingInterface;
use QuickCart\CustomApi\Api\Data\TrackingInterfaceFactory;
use QuickCart\CustomApi\Api\Data\TrackingNumberInterfaceFactory;
use QuickCart\CustomApi\Api\Data\TrackingStepInterfaceFactory;
use QuickCart\CustomApi\Api\Data\TrackingUpdateInterfaceFactory;
use QuickCart\OrderTracking\Model\TimelineBuilder;

/**
 * Order tracking timeline (QuickCart_OrderTracking) as REST data objects.
 */
class TrackingMapper
{
    /**
     * @param TimelineBuilder $timelineBuilder
     * @param TrackingInterfaceFactory $trackingFactory
     * @param TrackingStepInterfaceFactory $stepFactory
     * @param TrackingUpdateInterfaceFactory $updateFactory
     * @param TrackingNumberInterfaceFactory $numberFactory
     */
    public function __construct(
        private readonly TimelineBuilder $timelineBuilder,
        private readonly TrackingInterfaceFactory $trackingFactory,
        private readonly TrackingStepInterfaceFactory $stepFactory,
        private readonly TrackingUpdateInterfaceFactory $updateFactory,
        private readonly TrackingNumberInterfaceFactory $numberFactory
    ) {
    }

    /**
     * Tracking of an order.
     *
     * @param Order $order
     * @return TrackingInterface
     */
    public function map(Order $order): TrackingInterface
    {
        $data = $this->timelineBuilder->build($order);

        return $this->trackingFactory->create(['data' => [
            'order_number' => (string)$order->getIncrementId(),
            'placed_at' => $this->iso((string)$order->getCreatedAt()),
            'milestone' => $data['off_track'] ?? $data['milestone'],
            'milestone_label' => $data['off_track_label'] ?? $data['status_label'],
            'off_track' => $data['off_track'],
            'progress' => $data['progress'],
            'estimated_delivery_from' => $data['eta_from'],
            'estimated_delivery_to' => $data['eta_to'],
            'delivered_at' => $data['delivered_at'],
            'steps' => array_map(fn (array $step) => $this->stepFactory->create(['data' => [
                'code' => $step['code'],
                'label' => $step['label'],
                'state' => $step['state'],
                'reached_at' => $step['date_iso'],
            ]]), $data['steps']),
            'updates' => array_map(fn (array $event) => $this->updateFactory->create(['data' => [
                'milestone' => $event['milestone'],
                'title' => $event['title'],
                'comment' => $event['comment'],
                'location' => $event['location'],
                'created_at' => $event['date_iso'],
            ]]), $data['events']),
            'shipments' => array_map(fn (array $track) => $this->numberFactory->create(['data' => $track]), $data['tracks']),
        ]]);
    }

    /**
     * Database UTC time as ISO 8601.
     *
     * @param string $utc
     * @return string
     */
    public function iso(string $utc): string
    {
        return (new \DateTimeImmutable($utc ?: 'now', new \DateTimeZone('UTC')))->format(DATE_ATOM);
    }
}
