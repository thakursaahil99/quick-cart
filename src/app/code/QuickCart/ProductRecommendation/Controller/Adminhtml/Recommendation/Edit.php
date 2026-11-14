<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Controller\Adminhtml\Recommendation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use QuickCart\ProductRecommendation\Api\RecommendationRepositoryInterface;

/**
 * Create / edit a recommendation (UI form quickcart_product_recommendation_form).
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_ProductRecommendation::recommendations_save';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RecommendationRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RecommendationRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $title = __('New Recommendation');
        if ($id) {
            try {
                $this->repository->getById($id);
                $title = __('Edit Recommendation #%1', $id);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This recommendation no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        /** @var Page $page */
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('QuickCart_ProductRecommendation::recommendations');
        $page->getConfig()->getTitle()->prepend(__('Product Recommendations'));
        $page->getConfig()->getTitle()->prepend($title);

        return $page;
    }
}
