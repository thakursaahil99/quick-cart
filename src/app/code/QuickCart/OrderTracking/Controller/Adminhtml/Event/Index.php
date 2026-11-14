<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * All tracking updates across orders.
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_OrderTracking::tracking';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var Page $page */
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('QuickCart_OrderTracking::tracking');
        $page->getConfig()->getTitle()->prepend(__('Order Tracking Updates'));

        return $page;
    }
}
