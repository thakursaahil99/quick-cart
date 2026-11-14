<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order;
use QuickCart\OrderTracking\Model\TimelineBuilder;

/**
 * Tracking timeline for storefront templates (tracking page and My Orders).
 */
class Timeline implements ArgumentInterface
{
    /**
     * @param TimelineBuilder $timelineBuilder
     */
    public function __construct(
        private readonly TimelineBuilder $timelineBuilder
    ) {
    }

    /**
     * Whether tracking is shown in the order's store.
     *
     * @param Order $order
     * @return bool
     */
    public function isEnabled(Order $order): bool
    {
        return $this->timelineBuilder->isEnabled($order);
    }

    /**
     * Tracking data for templates (see TimelineBuilder::build()).
     *
     * @param Order $order
     * @return array
     */
    public function build(Order $order): array
    {
        return $this->timelineBuilder->build($order);
    }
}
