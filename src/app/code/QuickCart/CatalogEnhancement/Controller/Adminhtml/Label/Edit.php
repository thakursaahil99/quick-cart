<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use QuickCart\CatalogEnhancement\Api\LabelRepositoryInterface;

/**
 * Create / edit a label rule (UI form quickcart_product_label_form).
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_CatalogEnhancement::product_labels_save';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param LabelRepositoryInterface $labelRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly LabelRepositoryInterface $labelRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $title = __('New Product Label');
        if ($id) {
            try {
                $title = __('Edit Label "%1"', $this->labelRepository->getById($id)->getName());
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This label no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        /** @var Page $page */
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('QuickCart_CatalogEnhancement::product_labels');
        $page->getConfig()->getTitle()->prepend(__('Product Labels'));
        $page->getConfig()->getTitle()->prepend($title);

        return $page;
    }
}
