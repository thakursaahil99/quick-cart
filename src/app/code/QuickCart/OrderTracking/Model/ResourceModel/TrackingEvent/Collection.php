<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use QuickCart\OrderTracking\Api\Data\TrackingEventInterface;
use QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent as TrackingEventResource;
use QuickCart\OrderTracking\Model\TrackingEvent;

/**
 * Tracking event collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = TrackingEventInterface::EVENT_ID;

    /**
     * @var string
     */
    protected $_eventPrefix = 'quickcart_order_tracking_event_collection';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(TrackingEvent::class, TrackingEventResource::class);
    }
}
