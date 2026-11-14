<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Controller\Adminhtml\Target;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use QuickCart\SalesDashboard\Api\SalesTargetRepositoryInterface;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesTarget\CollectionFactory;

/**
 * Delete the targets selected in the grid.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_SalesDashboard::targets_delete';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param SalesTargetRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly SalesTargetRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $deleted = 0;
        foreach ($this->filter->getCollection($this->collectionFactory->create())->getItems() as $target) {
            $this->repository->delete($target);
            $deleted++;
        }
        $this->messageManager->addSuccessMessage(__('A total of %1 target(s) have been deleted.', $deleted));

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
