<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Controller\Adminhtml\Target;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\SalesDashboard\Api\SalesTargetRepositoryInterface;
use QuickCart\SalesDashboard\Model\SalesTargetFactory;

/**
 * Save a monthly target.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_SalesDashboard::targets_save';
    public const PERSISTOR_KEY = 'quickcart_sales_target';

    /**
     * @param Context $context
     * @param SalesTargetRepositoryInterface $repository
     * @param SalesTargetFactory $targetFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        private readonly SalesTargetRepositoryInterface $repository,
        private readonly SalesTargetFactory $targetFactory,
        private readonly DataPersistorInterface $dataPersistor
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
        $id = (int)($data['target_id'] ?? 0);
        try {
            $target = $id ? $this->repository->getById($id) : $this->targetFactory->create();
            $target->setPeriod((string)($data['period'] ?? ''))
                ->setRevenueTarget((float)($data['revenue_target'] ?? 0))
                ->setOrdersTarget((int)($data['orders_target'] ?? 0))
                ->setNote(isset($data['note']) ? (string)$data['note'] : null);
            $this->repository->save($target);

            $this->messageManager->addSuccessMessage(__('The target for %1 has been saved.', $target->getPeriod()));
            $this->dataPersistor->clear(self::PERSISTOR_KEY);
            if ($this->getRequest()->getParam('back') === 'continue') {
                return $redirect->setPath('*/*/edit', ['id' => $target->getTargetId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This target no longer exists.'));
            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the target.'));
        }

        $this->dataPersistor->set(self::PERSISTOR_KEY, $data);

        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/new');
    }
}
