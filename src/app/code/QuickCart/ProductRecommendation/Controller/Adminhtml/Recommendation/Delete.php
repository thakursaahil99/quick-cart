<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Controller\Adminhtml\Recommendation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use QuickCart\ProductRecommendation\Api\RecommendationRepositoryInterface;

/**
 * Delete one recommendation.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_ProductRecommendation::recommendations_delete';

    /**
     * @param Context $context
     * @param RecommendationRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly RecommendationRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->repository->deleteById((int)$this->getRequest()->getParam('id'));
            $this->messageManager->addSuccessMessage(__('The recommendation has been deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
