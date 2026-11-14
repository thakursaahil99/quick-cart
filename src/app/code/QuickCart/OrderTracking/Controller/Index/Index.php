<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use QuickCart\OrderTracking\Model\Config;

/**
 * GET /track - order tracking form.
 */
class Index implements HttpGetActionInterface
{
    /**
     * @param PageFactory $pageFactory
     * @param ForwardFactory $forwardFactory
     * @param Config $config
     */
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly Config $config
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->config->isEnabled()) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Track Your Order'));

        return $page;
    }
}
