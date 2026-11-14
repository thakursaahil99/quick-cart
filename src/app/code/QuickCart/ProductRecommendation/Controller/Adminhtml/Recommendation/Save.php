<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Controller\Adminhtml\Recommendation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;
use QuickCart\ProductRecommendation\Api\RecommendationRepositoryInterface;
use QuickCart\ProductRecommendation\Model\RecommendationFactory;

/**
 * Save a recommendation. Anything saved from the admin becomes "manual", so the
 * nightly rebuild never overwrites a merchandiser's decision.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_ProductRecommendation::recommendations_save';
    public const PERSISTOR_KEY = 'quickcart_product_recommendation';

    /**
     * @param Context $context
     * @param RecommendationRepositoryInterface $repository
     * @param RecommendationFactory $recommendationFactory
     * @param ProductResource $productResource
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        private readonly RecommendationRepositoryInterface $repository,
        private readonly RecommendationFactory $recommendationFactory,
        private readonly ProductResource $productResource,
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
        $id = (int)($data['recommendation_id'] ?? 0);

        try {
            $recommendation = $id ? $this->repository->getById($id) : $this->recommendationFactory->create();
            $recommendation->setProductId($this->resolveSku((string)($data['product_sku'] ?? ''), __('Product')))
                ->setRecommendedProductId(
                    $this->resolveSku((string)($data['recommended_sku'] ?? ''), __('Recommended product'))
                )
                ->setType((string)($data['type'] ?? RecommendationInterface::TYPE_BOUGHT_TOGETHER))
                ->setSource(RecommendationInterface::SOURCE_MANUAL)
                ->setPosition((int)($data['position'] ?? 0))
                ->setIsActive((bool)($data['is_active'] ?? false));
            $this->repository->save($recommendation);

            $this->messageManager->addSuccessMessage(__('The recommendation has been saved.'));
            $this->dataPersistor->clear(self::PERSISTOR_KEY);
            if ($this->getRequest()->getParam('back') === 'continue') {
                return $redirect->setPath('*/*/edit', ['id' => $recommendation->getRecommendationId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the recommendation.'));
        }

        $this->dataPersistor->set(self::PERSISTOR_KEY, $data);

        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/new');
    }

    /**
     * Product ID for a SKU.
     *
     * @param string $sku
     * @param \Magento\Framework\Phrase $field
     * @return int
     * @throws NoSuchEntityException
     */
    private function resolveSku(string $sku, \Magento\Framework\Phrase $field): int
    {
        $sku = trim($sku);
        $id = $sku === '' ? 0 : (int)$this->productResource->getIdBySku($sku);
        if (!$id) {
            throw new NoSuchEntityException(__('%1: no product with SKU "%2".', $field, $sku));
        }

        return $id;
    }
}
