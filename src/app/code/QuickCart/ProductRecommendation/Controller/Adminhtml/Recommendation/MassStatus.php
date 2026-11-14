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
use Magento\Ui\Component\MassAction\Filter;
use QuickCart\ProductRecommendation\Api\RecommendationRepositoryInterface;
use QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation\CollectionFactory;

/**
 * Enable (status=1) or hide (status=0) the selected recommendations.
 *
 * Hiding an automatic row keeps it hidden after rebuilds only once it is made manual,
 * so status changes also switch the row to the "manual" source.
 */
class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_ProductRecommendation::recommendations_save';

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
        $active = (int)$this->getRequest()->getParam('status') === 1;
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $updated = 0;
        try {
            foreach ($collection->getItems() as $recommendation) {
                if ($recommendation->getIsActive() !== $active) {
                    $recommendation->setIsActive($active)->setSource($recommendation::SOURCE_MANUAL);
                    $this->repository->save($recommendation);
                    $updated++;
                }
            }
            $this->messageManager->addSuccessMessage(
                __('A total of %1 recommendation(s) have been updated.', $updated)
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
