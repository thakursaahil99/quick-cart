<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use QuickCart\OrderTracking\Api\TrackingEventRepositoryInterface;
use QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent\CollectionFactory;

/**
 * Delete the tracking updates selected in the grid.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_OrderTracking::tracking_delete';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param TrackingEventRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly TrackingEventRepositoryInterface $repository
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
        foreach ($collection->getItems() as $event) {
            $this->repository->delete($event);
            $deleted++;
        }
        $this->messageManager->addSuccessMessage(__('A total of %1 tracking update(s) have been deleted.', $deleted));

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
