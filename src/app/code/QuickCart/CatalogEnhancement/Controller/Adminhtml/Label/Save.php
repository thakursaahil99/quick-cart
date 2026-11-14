<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\Filter\Date as DateFilter;
use QuickCart\CatalogEnhancement\Api\LabelRepositoryInterface;
use QuickCart\CatalogEnhancement\Model\LabelFactory;

/**
 * Save a label rule posted by the UI form.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_CatalogEnhancement::product_labels_save';
    public const PERSISTOR_KEY = 'quickcart_product_label';

    /**
     * @param Context $context
     * @param LabelRepositoryInterface $labelRepository
     * @param LabelFactory $labelFactory
     * @param DataPersistorInterface $dataPersistor
     * @param DateFilter $dateFilter
     */
    public function __construct(
        Context $context,
        private readonly LabelRepositoryInterface $labelRepository,
        private readonly LabelFactory $labelFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly DateFilter $dateFilter
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = (array)$this->getRequest()->getPostValue();
        if (!$data) {
            return $redirect->setPath('*/*/');
        }
        $id = (int)($data['label_id'] ?? 0);

        try {
            $label = $id ? $this->labelRepository->getById($id) : $this->labelFactory->create();
            $storeIds = $data['store_ids'] ?? [0];
            $label->setName((string)($data['name'] ?? ''))
                ->setText((string)($data['text'] ?? ''))
                ->setConditionType((string)($data['condition_type'] ?? ''))
                ->setConditionValue(isset($data['condition_value']) ? (string)$data['condition_value'] : null)
                ->setBackgroundColor((string)($data['background_color'] ?? ''))
                ->setTextColor((string)($data['text_color'] ?? ''))
                ->setSortOrder((int)($data['sort_order'] ?? 0))
                ->setIsActive((bool)($data['is_active'] ?? false))
                ->setFromDate($this->toIsoDate($data['from_date'] ?? null))
                ->setToDate($this->toIsoDate($data['to_date'] ?? null))
                ->setStoreIds(array_map('intval', is_array($storeIds) ? $storeIds : explode(',', (string)$storeIds)));
            $this->labelRepository->save($label);

            $this->messageManager->addSuccessMessage(__('The label "%1" has been saved.', $label->getName()));
            $this->dataPersistor->clear(self::PERSISTOR_KEY);
            if ($this->getRequest()->getParam('back') === 'continue') {
                return $redirect->setPath('*/*/edit', ['id' => $label->getLabelId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This label no longer exists.'));
            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the label.'));
        }

        $this->dataPersistor->set(self::PERSISTOR_KEY, $data);

        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/new');
    }

    /**
     * Admin date input (locale format or Y-m-d) to Y-m-d; invalid input is left for the validator.
     *
     * @param mixed $value
     * @return string|null
     */
    private function toIsoDate(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return substr($value, 0, 10);
        }
        try {
            return substr((string)$this->dateFilter->filter($value), 0, 10);
        } catch (\Exception $e) {
            return $value;
        }
    }
}
