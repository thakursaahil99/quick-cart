<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment;
use QuickCart\OrderTracking\Model\EventRecorder;
use QuickCart\OrderTracking\Model\Milestone;

/**
 * sales_order_shipment_save_after: a new shipment marks the order as shipped.
 */
class RecordShipment implements ObserverInterface
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
        $shipment = $observer->getEvent()->getData('shipment');
        if (!$shipment instanceof Shipment || !$shipment->getId() || !$shipment->getOrderId()) {
            return;
        }
        $this->recorder->record(
            (int)$shipment->getOrderId(),
            Milestone::SHIPPED,
            (string)__('Shipped'),
            (string)__('Shipment #%1 with %2 item(s) has left our warehouse.', $shipment->getIncrementId(), (float)$shipment->getTotalQty()),
            'shipment:' . $shipment->getId()
        );
    }
}
