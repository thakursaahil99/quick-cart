<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Plugin\Sales;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use QuickCart\CheckoutEnhancement\Api\CheckoutMetaRepositoryInterface;
use QuickCart\CheckoutEnhancement\Api\Data\CheckoutMetaInterface;

/**
 * Exposes delivery instructions / GSTIN as order extension attributes
 * (qc_delivery_note, qc_gstin) - visible to integrations and the REST API.
 */
class AddCheckoutMetaToOrder
{
    /**
     * @param CheckoutMetaRepositoryInterface $repository
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(
        private readonly CheckoutMetaRepositoryInterface $repository,
        private readonly OrderExtensionFactory $extensionFactory
    ) {
    }

    /**
     * After plugin for get().
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        $metas = $this->repository->getByOrderIds([(int)$order->getEntityId()]);
        $this->apply($order, $metas[(int)$order->getEntityId()] ?? null);

        return $order;
    }

    /**
     * After plugin for getList() - one query for the whole page.
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $result
     * @return OrderSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $result
    ): OrderSearchResultInterface {
        $orders = $result->getItems();
        $metas = $this->repository->getByOrderIds(array_map(
            static fn (OrderInterface $order): int => (int)$order->getEntityId(),
            $orders
        ));
        foreach ($orders as $order) {
            $this->apply($order, $metas[(int)$order->getEntityId()] ?? null);
        }

        return $result;
    }

    /**
     * Copy checkout details onto the order's extension attributes.
     *
     * @param OrderInterface $order
     * @param CheckoutMetaInterface|null $meta
     * @return void
     */
    private function apply(OrderInterface $order, ?CheckoutMetaInterface $meta): void
    {
        if ($meta === null) {
            return;
        }
        $extension = $order->getExtensionAttributes() ?? $this->extensionFactory->create();
        $extension->setQcDeliveryNote($meta->getDeliveryNote());
        $extension->setQcGstin($meta->getGstin());
        $order->setExtensionAttributes($extension);
    }
}
