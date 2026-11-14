<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Controller\Adminhtml\Target;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use QuickCart\SalesDashboard\Api\SalesTargetRepositoryInterface;

/**
 * Delete a target.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_SalesDashboard::targets_delete';

    /**
     * @param Context $context
     * @param SalesTargetRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly SalesTargetRepositoryInterface $repository
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
            $this->messageManager->addSuccessMessage(__('The target has been deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
