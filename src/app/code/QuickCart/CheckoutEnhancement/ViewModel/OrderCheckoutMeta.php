<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use QuickCart\CheckoutEnhancement\Api\CheckoutMetaRepositoryInterface;
use QuickCart\CheckoutEnhancement\Api\Data\CheckoutMetaInterface;

/**
 * Delivery details for the order being viewed (customer account, admin) or for
 * the order just placed (success page).
 *
 * Access control is the page controller's job: the customer order view and the
 * admin order view only render after the order was authorised for the viewer.
 */
class OrderCheckoutMeta implements ArgumentInterface
{
    /**
     * @param CheckoutMetaRepositoryInterface $repository
     * @param RequestInterface $request
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        private readonly CheckoutMetaRepositoryInterface $repository,
        private readonly RequestInterface $request,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    /**
     * Details for the order_id request parameter.
     *
     * @return CheckoutMetaInterface|null
     */
    public function getForCurrentOrder(): ?CheckoutMetaInterface
    {
        return $this->find((int)$this->request->getParam('order_id'));
    }

    /**
     * Details for the order placed in this session (checkout success page).
     *
     * @return CheckoutMetaInterface|null
     */
    public function getForLastOrder(): ?CheckoutMetaInterface
    {
        return $this->find((int)$this->checkoutSession->getLastOrderId());
    }

    /**
     * Whether the details contain anything to display.
     *
     * @param CheckoutMetaInterface|null $meta
     * @return bool
     */
    public function hasContent(?CheckoutMetaInterface $meta): bool
    {
        return $meta !== null && ((string)$meta->getDeliveryNote() !== '' || (string)$meta->getGstin() !== '');
    }

    /**
     * Load details for an order id.
     *
     * @param int $orderId
     * @return CheckoutMetaInterface|null
     */
    private function find(int $orderId): ?CheckoutMetaInterface
    {
        if ($orderId <= 0) {
            return null;
        }
        try {
            return $this->repository->getByOrderId($orderId);
        } catch (NoSuchEntityException) {
            return null;
        }
    }
}
