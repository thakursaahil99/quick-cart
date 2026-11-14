<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\OrderTracking\Model\Config;
use QuickCart\OrderTracking\Model\OrderLookup;
use QuickCart\OrderTracking\Model\TrackingSession;

/**
 * POST /track/index/lookup - order number + email or mobile number.
 *
 * Form keys are checked by Magento's CSRF validator for every storefront POST.
 * Failures get one generic message and are rate limited per visitor session.
 */
class Lookup implements HttpPostActionInterface
{
    /**
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param ManagerInterface $messageManager
     * @param StoreManagerInterface $storeManager
     * @param OrderLookup $orderLookup
     * @param TrackingSession $trackingSession
     * @param Config $config
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly StoreManagerInterface $storeManager,
        private readonly OrderLookup $orderLookup,
        private readonly TrackingSession $trackingSession,
        private readonly Config $config
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        if (!$this->config->isEnabled()) {
            return $redirect->setPath('');
        }
        if ($this->trackingSession->isLocked($this->config->getMaxLookupAttempts())) {
            $this->messageManager->addErrorMessage(
                __('Too many attempts. Please wait a few minutes and try again.')
            );
            return $redirect->setPath('track');
        }

        $order = $this->orderLookup->find(
            (string)$this->request->getParam('order_number'),
            (string)$this->request->getParam('contact'),
            (int)$this->storeManager->getStore()->getId()
        );
        if (!$order) {
            $this->trackingSession->addFailure();
            $this->messageManager->addErrorMessage(
                __('We could not find an order with those details. Check the order number and the email or mobile number used at checkout.')
            );
            return $redirect->setPath('track');
        }

        $this->trackingSession->allow((int)$order->getEntityId());

        return $redirect->setPath('track/index/view', ['order' => $order->getIncrementId()]);
    }
}
