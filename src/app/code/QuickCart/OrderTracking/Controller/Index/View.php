<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\OrderTracking\Model\Config;
use QuickCart\OrderTracking\Model\OrderLookup;
use QuickCart\OrderTracking\Model\TrackingSession;

/**
 * GET /track/index/view/order/{number} - tracking page of an order this visitor unlocked
 * (or placed while logged in). Not full-page cached (see layout).
 */
class View implements HttpGetActionInterface
{
    public const REGISTRY_KEY = 'qc_tracked_order';

    /**
     * @param RequestInterface $request
     * @param PageFactory $pageFactory
     * @param RedirectFactory $redirectFactory
     * @param ManagerInterface $messageManager
     * @param CollectionFactory $orderCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param TrackingSession $trackingSession
     * @param Registry $registry
     * @param Config $config
     * @param OrderLookup $orderLookup
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PageFactory $pageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly TrackingSession $trackingSession,
        private readonly Registry $registry,
        private readonly Config $config,
        private readonly OrderLookup $orderLookup
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->config->isEnabled()) {
            return $this->redirectFactory->create()->setPath('');
        }
        $incrementId = $this->orderLookup->normalizeIncrementId((string)$this->request->getParam('order'));
        /** @var Order $order */
        $order = $this->orderCollectionFactory->create()
            ->addFieldToFilter('increment_id', $incrementId ?: '-')
            ->addFieldToFilter('store_id', (int)$this->storeManager->getStore()->getId())
            ->setPageSize(1)
            ->getFirstItem();

        $customerId = $order->getCustomerId() ? (int)$order->getCustomerId() : null;
        if (!$order->getId() || !$this->trackingSession->canView((int)$order->getId(), $customerId)) {
            $this->messageManager->addNoticeMessage(__('Enter your order details to see its tracking.'));
            return $this->redirectFactory->create()->setPath('track');
        }

        $this->registry->register(self::REGISTRY_KEY, $order);
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Order #%1', $order->getIncrementId()));

        return $page;
    }
}
