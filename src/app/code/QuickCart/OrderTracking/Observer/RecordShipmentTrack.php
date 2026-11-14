<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use QuickCart\OrderTracking\Model\EventRecorder;
use QuickCart\OrderTracking\Model\Milestone;

/**
 * sales_order_shipment_track_save_after: carrier and tracking number on the timeline.
 */
class RecordShipmentTrack implements ObserverInterface
{
    /**
     * @param EventRecorder $recorder
     */
    public function __construct(
        private readonly EventRecorder $recorder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        $track = $observer->getEvent()->getData('track');
        if (!$track instanceof Track || !$track->getId() || !$track->getOrderId()) {
            return;
        }
        $carrier = (string)($track->getTitle() ?: $track->getCarrierCode());
        $this->recorder->record(
            (int)$track->getOrderId(),
            Milestone::SHIPPED,
            (string)__('Handed over to %1', $carrier),
            (string)__('Tracking number: %1', $track->getTrackNumber()),
            'track:' . $track->getId()
        );
    }
}
