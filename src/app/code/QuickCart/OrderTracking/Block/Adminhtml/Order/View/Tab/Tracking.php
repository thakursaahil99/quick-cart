<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use QuickCart\OrderTracking\Api\Data\TrackingEventInterface;
use QuickCart\OrderTracking\Api\TrackingEventRepositoryInterface;
use QuickCart\OrderTracking\Model\Milestone;

/**
 * "Tracking" tab on the admin order view: timeline and a form for manual updates.
 */
class Tracking extends Template implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'QuickCart_OrderTracking::order/view/tab/tracking.phtml';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param TrackingEventRepositoryInterface $repository
     * @param Milestone $milestone
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly TrackingEventRepositoryInterface $repository,
        private readonly Milestone $milestone,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Order being viewed.
     *
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        $order = $this->registry->registry('current_order');

        return $order instanceof Order ? $order : null;
    }

    /**
     * Timeline events, newest first.
     *
     * @return TrackingEventInterface[]
     */
    public function getEvents(): array
    {
        $order = $this->getOrder();

        return $order ? array_reverse($this->repository->getByOrderId((int)$order->getEntityId())) : [];
    }

    /**
     * Milestone => label.
     *
     * @return array<string, string>
     */
    public function getMilestoneLabels(): array
    {
        return $this->milestone->getLabels();
    }

    /**
     * Milestones that can also move the order to a matching status right now.
     *
     * @return string[]
     */
    public function getStatusMilestones(): array
    {
        $order = $this->getOrder();
        if (!$order || !in_array($order->getState(), [Order::STATE_NEW, Order::STATE_PROCESSING], true)) {
            return [];
        }

        return array_keys(Milestone::STATUS_FOR_MILESTONE);
    }

    /**
     * Whether the admin may add updates.
     *
     * @return bool
     */
    public function canAddUpdates(): bool
    {
        return $this->_authorization->isAllowed('QuickCart_OrderTracking::tracking_save');
    }

    /**
     * Event time in the admin's locale and the store timezone.
     *
     * @param TrackingEventInterface $event
     * @return string
     */
    public function formatEventTime(TrackingEventInterface $event): string
    {
        return $this->formatDate($event->getCreatedAt(), \IntlDateFormatter::MEDIUM, true);
    }

    /**
     * Form action.
     *
     * @return string
     */
    public function getSaveUrl(): string
    {
        return $this->getUrl('quickcart_tracking/event/save');
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Tracking');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Order Tracking Timeline');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return $this->_authorization->isAllowed('QuickCart_OrderTracking::tracking');
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }
}
