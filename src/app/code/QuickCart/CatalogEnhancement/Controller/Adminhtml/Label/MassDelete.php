<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use QuickCart\CatalogEnhancement\Api\LabelRepositoryInterface;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label\CollectionFactory;

/**
 * Delete the label rules selected in the grid.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_CatalogEnhancement::product_labels_delete';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param LabelRepositoryInterface $labelRepository
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly LabelRepositoryInterface $labelRepository
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
        foreach ($collection->getItems() as $label) {
            $this->labelRepository->delete($label);
            $deleted++;
        }
        $this->messageManager->addSuccessMessage(__('A total of %1 label(s) have been deleted.', $deleted));

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
