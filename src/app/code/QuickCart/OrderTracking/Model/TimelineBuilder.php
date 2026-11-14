<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use DateTimeImmutable;
use IntlDateFormatter;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use QuickCart\OrderTracking\Api\TrackingEventRepositoryInterface;
use QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent as TrackingEventResource;

/**
 * Customer-facing tracking view of an order: progress steps, timeline events, carrier
 * tracking numbers and the estimated delivery window.
 *
 * Shared by the storefront templates (ViewModel\Timeline) and the QuickCart REST API.
 * Every date comes both formatted for display and as ISO 8601.
 */
class TimelineBuilder
{
    private const OFF_TRACK = [Milestone::CANCELED, Milestone::CLOSED, Milestone::ON_HOLD];

    /**
     * @param TrackingEventRepositoryInterface $repository
     * @param Milestone $milestone
     * @param Config $config
     * @param TimezoneInterface $timezone
     * @param EstimatedDelivery $estimatedDelivery
     * @param TrackingEventResource $eventResource
     */
    public function __construct(
        private readonly TrackingEventRepositoryInterface $repository,
        private readonly Milestone $milestone,
        private readonly Config $config,
        private readonly TimezoneInterface $timezone,
        private readonly EstimatedDelivery $estimatedDelivery,
        private readonly TrackingEventResource $eventResource
    ) {
    }

    /**
     * Furthest milestone of an order (one query), or its off-track milestone.
     *
     * Lightweight version of build() for order lists.
     *
     * @param Order $order
     * @return string
     */
    public function getCurrentMilestone(Order $order): string
    {
        $current = $this->milestone->fromOrderState((string)$order->getState(), (string)$order->getStatus())
            ?? Milestone::PLACED;
        if (in_array($current, self::OFF_TRACK, true)) {
            return $current;
        }
        $reached = max(0, $this->milestone->stepIndex($current));
        foreach ($this->eventResource->getMilestones((int)$order->getEntityId()) as $milestone) {
            $reached = max($reached, $this->milestone->stepIndex($milestone));
        }

        return Milestone::STEPS[$reached];
    }

    /**
     * Whether tracking is enabled in the order's store.
     *
     * @param Order $order
     * @return bool
     */
    public function isEnabled(Order $order): bool
    {
        return $this->config->isEnabled((int)$order->getStoreId());
    }

    /**
     * Tracking data of an order (customer-visible events only).
     *
     * @param Order $order
     * @return array{
     *     milestone: string, status_label: string, off_track: string|null, off_track_label: string|null,
     *     progress: int, steps: array, events: array, tracks: array, eta: string|null, eta_from: string|null,
     *     eta_to: string|null, delivered_on: string|null, delivered_at: string|null
     * }
     */
    public function build(Order $order): array
    {
        $events = $this->repository->getByOrderId((int)$order->getEntityId(), true);
        $current = $this->milestone->fromOrderState((string)$order->getState(), (string)$order->getStatus())
            ?? Milestone::PLACED;

        // Furthest step reached by the order status or by any timeline event.
        $reached = max(0, $this->milestone->stepIndex($current));
        $stepDates = [Milestone::PLACED => $this->toLocal((string)$order->getCreatedAt())];
        foreach ($events as $event) {
            $index = $this->milestone->stepIndex($event->getMilestone());
            if ($index >= 0) {
                $reached = max($reached, $index);
                $stepDates[$event->getMilestone()] ??= $this->toLocal((string)$event->getCreatedAt());
            }
        }
        $offTrack = in_array($current, self::OFF_TRACK, true) ? $current : null;

        $steps = [];
        foreach (Milestone::STEPS as $index => $code) {
            $state = $index < $reached ? 'done' : ($index === $reached ? 'current' : 'upcoming');
            if ($index === $reached && $code === Milestone::DELIVERED) {
                $state = 'done';
            }
            $date = isset($stepDates[$code]) && $index <= $reached ? $stepDates[$code] : null;
            $steps[] = [
                'code' => $code,
                'label' => $this->milestone->getLabel($code),
                'state' => $state,
                'date' => $date ? $this->format($date, 'EEE, d MMM') : null,
                'date_iso' => $date?->format(DATE_ATOM),
            ];
        }

        $timeline = [];
        foreach (array_reverse($events) as $event) {
            $date = $this->toLocal((string)$event->getCreatedAt());
            $timeline[] = [
                'milestone' => $event->getMilestone(),
                'title' => $event->getTitle(),
                'comment' => $event->getComment(),
                'location' => $event->getLocation(),
                'date' => $this->format($date, 'd MMM y, h:mm a'),
                'date_iso' => $date->format(DATE_ATOM),
            ];
        }

        $milestone = Milestone::STEPS[$reached];
        $delivered = $milestone === Milestone::DELIVERED;
        $window = $delivered || $offTrack ? null : $this->getEtaWindow($order);
        $deliveredAt = $delivered ? ($stepDates[Milestone::DELIVERED] ?? null) : null;

        return [
            'milestone' => $milestone,
            // Furthest milestone, not the raw status: Magento resets the status to
            // "Processing" when shipments / invoices are created.
            'status_label' => $this->milestone->getLabel($milestone),
            'off_track' => $offTrack,
            'off_track_label' => $offTrack ? $this->milestone->getLabel($offTrack) : null,
            'progress' => (int)round($reached / (count(Milestone::STEPS) - 1) * 100),
            'steps' => $steps,
            'events' => $timeline,
            'tracks' => $this->getTracks($order),
            'eta' => $window ? $this->formatWindow($window) : null,
            'eta_from' => $window ? $window['from']->format('Y-m-d') : null,
            'eta_to' => $window ? $window['to']->format('Y-m-d') : null,
            'delivered_on' => $deliveredAt ? $this->format($deliveredAt, 'EEEE, d MMMM') : null,
            'delivered_at' => $deliveredAt?->format(DATE_ATOM),
        ];
    }

    /**
     * Carrier tracking numbers of all shipments.
     *
     * @param Order $order
     * @return array<int, array{carrier: string, number: string}>
     */
    private function getTracks(Order $order): array
    {
        $tracks = [];
        foreach ($order->getTracksCollection() as $track) {
            $tracks[] = [
                'carrier' => (string)($track->getTitle() ?: $track->getCarrierCode()),
                'number' => (string)$track->getTrackNumber(),
            ];
        }

        return $tracks;
    }

    /**
     * Configured business-day delivery window.
     *
     * @param Order $order
     * @return array{from: DateTimeImmutable, to: DateTimeImmutable}
     */
    private function getEtaWindow(Order $order): array
    {
        $storeId = (int)$order->getStoreId();

        return $this->estimatedDelivery->window(
            $this->toLocal((string)$order->getCreatedAt()),
            $this->config->getEtaMinDays($storeId),
            $this->config->getEtaMaxDays($storeId)
        );
    }

    /**
     * "Mon, 17 Nov – Wed, 19 Nov".
     *
     * @param array{from: DateTimeImmutable, to: DateTimeImmutable} $window
     * @return string
     */
    private function formatWindow(array $window): string
    {
        $from = $this->format($window['from'], 'EEE, d MMM');
        $to = $this->format($window['to'], 'EEE, d MMM');

        return $from === $to ? $from : $from . ' – ' . $to;
    }

    /**
     * UTC database time to the store timezone.
     *
     * @param string $utc
     * @return DateTimeImmutable
     */
    private function toLocal(string $utc): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable(
            $this->timezone->date(new \DateTime($utc ?: 'now', new \DateTimeZone('UTC')))
        );
    }

    /**
     * Locale-aware date formatting.
     *
     * @param DateTimeImmutable $date
     * @param string $pattern ICU pattern
     * @return string
     */
    private function format(DateTimeImmutable $date, string $pattern): string
    {
        return (string)$this->timezone->formatDateTime(
            $date,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            null,
            $date->getTimezone()->getName(),
            $pattern
        );
    }
}
