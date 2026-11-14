<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Controller\Adminhtml\Target;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use QuickCart\SalesDashboard\Api\SalesTargetRepositoryInterface;

/**
 * Create / edit a monthly target (UI form quickcart_sales_target_form).
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_SalesDashboard::targets_save';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SalesTargetRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly SalesTargetRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $title = __('New Sales Target');
        if ($id) {
            try {
                $title = __('Sales Target %1', $this->repository->getById($id)->getPeriod());
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This target no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        /** @var Page $page */
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('QuickCart_SalesDashboard::targets');
        $page->getConfig()->getTitle()->prepend(__('Sales Targets'));
        $page->getConfig()->getTitle()->prepend($title);

        return $page;
    }
}
