<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use QuickCart\CatalogEnhancement\Api\LabelRepositoryInterface;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label\CollectionFactory;

/**
 * Enable (status=1) or disable (status=0) the label rules selected in the grid.
 */
class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_CatalogEnhancement::product_labels_save';

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
        $active = (int)$this->getRequest()->getParam('status') === 1;
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $updated = 0;
        try {
            foreach ($collection->getItems() as $label) {
                if ($label->getIsActive() !== $active) {
                    $this->labelRepository->save($label->setIsActive($active));
                    $updated++;
                }
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 label(s) have been updated.', $updated));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
