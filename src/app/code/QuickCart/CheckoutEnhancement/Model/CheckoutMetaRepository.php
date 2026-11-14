<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\CheckoutEnhancement\Api\CheckoutMetaRepositoryInterface;
use QuickCart\CheckoutEnhancement\Api\Data\CheckoutMetaInterface;
use QuickCart\CheckoutEnhancement\Model\ResourceModel\CheckoutMeta as CheckoutMetaResource;
use QuickCart\CheckoutEnhancement\Model\ResourceModel\CheckoutMeta\CollectionFactory;

/**
 * Repository for checkout details.
 */
class CheckoutMetaRepository implements CheckoutMetaRepositoryInterface
{
    /**
     * @param CheckoutMetaFactory $metaFactory
     * @param CheckoutMetaResource $resource
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CheckoutMetaFactory $metaFactory,
        private readonly CheckoutMetaResource $resource,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(CheckoutMetaInterface $meta): CheckoutMetaInterface
    {
        try {
            /** @var CheckoutMeta $meta */
            $this->resource->save($meta);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the checkout details: %1', $e->getMessage()), $e);
        }

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function getByQuoteId(int $quoteId): CheckoutMetaInterface
    {
        return $this->loadBy(CheckoutMetaInterface::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritdoc
     */
    public function getByOrderId(int $orderId): CheckoutMetaInterface
    {
        return $this->loadBy(CheckoutMetaInterface::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds)));
        if (!$orderIds) {
            return [];
        }
        $result = [];
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(CheckoutMetaInterface::ORDER_ID, ['in' => $orderIds]);
        foreach ($collection as $meta) {
            $result[(int)$meta->getOrderId()] = $meta;
        }

        return $result;
    }

    /**
     * Load by a unique column.
     *
     * @param string $field
     * @param int $value
     * @return CheckoutMetaInterface
     * @throws NoSuchEntityException
     */
    private function loadBy(string $field, int $value): CheckoutMetaInterface
    {
        $meta = $this->metaFactory->create();
        $this->resource->load($meta, $value, $field);
        if (!$meta->getId()) {
            throw new NoSuchEntityException(__('No checkout details found for %1 "%2".', $field, $value));
        }

        return $meta;
    }
}
