<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use QuickCart\OrderTracking\Api\TrackingEventRepositoryInterface;

/**
 * Delete one tracking update.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_OrderTracking::tracking_delete';

    /**
     * @param Context $context
     * @param TrackingEventRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly TrackingEventRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->repository->delete($this->repository->getById((int)$this->getRequest()->getParam('id')));
            $this->messageManager->addSuccessMessage(__('The tracking update has been deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
