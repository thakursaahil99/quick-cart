<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use QuickCart\OrderTracking\Model\TrackingUpdater;

/**
 * Add a manual tracking update from the order view "Tracking" tab, optionally moving
 * the order to the matching status (Confirmed / Packed / Shipped / Out for Delivery).
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_OrderTracking::tracking_save';

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param TrackingUpdater $trackingUpdater
     */
    public function __construct(
        Context $context,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TrackingUpdater $trackingUpdater
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $request = $this->getRequest();
        $orderId = (int)$request->getParam('order_id');
        $redirect = $this->resultRedirectFactory->create();

        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);
            $event = $this->trackingUpdater->addUpdate(
                $order,
                (string)$request->getParam('milestone'),
                (string)$request->getParam('title'),
                (string)$request->getParam('comment'),
                (string)$request->getParam('location'),
                (bool)$request->getParam('is_visible_on_front'),
                (bool)$request->getParam('update_status'),
                $this->_auth->getUser() ? (string)$this->_auth->getUser()->getUserName() : null
            );
            $this->messageManager->addSuccessMessage(__('Tracking update "%1" added.', $event->getTitle()));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('The tracking update could not be saved.'));
        }

        return $orderId
            ? $redirect->setPath('sales/order/view', ['order_id' => $orderId, 'active_tab' => 'qc_tracking'])
            : $redirect->setPath('sales/order/');
    }
}
