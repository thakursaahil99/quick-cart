<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use QuickCart\OrderTracking\Model\EventRecorder;

/**
 * sales_order_save_after: placing, confirming, packing, completing or cancelling an
 * order adds the matching milestone to its timeline (once per change).
 */
class RecordOrderStatus implements ObserverInterface
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
        $order = $observer->getEvent()->getData('order');
        if ($order instanceof Order) {
            $this->recorder->recordStatus($order);
        }
    }
}
