<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Block;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use QuickCart\OrderTracking\Controller\Index\View;

/**
 * Order shown on the tracking page (registered by the View controller) or on the
 * customer account order page ("current_order").
 */
class TrackedOrder extends Template
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * The order, or null.
     *
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        $order = $this->registry->registry(View::REGISTRY_KEY) ?? $this->registry->registry('current_order');

        return $order instanceof Order ? $order : null;
    }

    /**
     * Order amount in the order currency.
     *
     * @param float $amount
     * @param Order $order
     * @return string
     */
    public function formatOrderPrice(float $amount, Order $order): string
    {
        return $this->priceCurrency->format($amount, false, 2, null, $order->getOrderCurrencyCode());
    }

    /**
     * Order date in the store timezone.
     *
     * @param Order $order
     * @return string
     */
    public function getOrderDate(Order $order): string
    {
        return $this->formatDate($order->getCreatedAt(), \IntlDateFormatter::MEDIUM);
    }

    /**
     * City, region and PIN code of the delivery address (no street or name on public pages).
     *
     * @param Order $order
     * @return string
     */
    public function getDeliveryArea(Order $order): string
    {
        $address = $order->getShippingAddress() ?: $order->getBillingAddress();
        if (!$address) {
            return '';
        }

        return implode(', ', array_filter([
            (string)$address->getCity(),
            (string)$address->getRegion(),
            (string)$address->getPostcode(),
        ]));
    }
}
