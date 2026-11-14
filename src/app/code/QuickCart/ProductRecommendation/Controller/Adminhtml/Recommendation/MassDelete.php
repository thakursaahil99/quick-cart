<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Controller\Adminhtml\Recommendation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use QuickCart\ProductRecommendation\Api\RecommendationRepositoryInterface;
use QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation\CollectionFactory;

/**
 * Delete the recommendations selected in the grid.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_ProductRecommendation::recommendations_delete';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param RecommendationRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly RecommendationRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted = 0;
        foreach ($collection->getItems() as $recommendation) {
            $this->repository->delete($recommendation);
            $deleted++;
        }
        $this->messageManager->addSuccessMessage(__('A total of %1 recommendation(s) have been deleted.', $deleted));

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
