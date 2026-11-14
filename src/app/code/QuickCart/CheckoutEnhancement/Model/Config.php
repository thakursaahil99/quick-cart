<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Stores > Configuration > QuickCart > Checkout.
 */
class Config
{
    private const PREFIX = 'quickcart_checkout/';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * "Delivery instructions" field on the shipping step.
     *
     * @return bool
     */
    public function isDeliveryNoteEnabled(): bool
    {
        return $this->flag('fields/delivery_note');
    }

    /**
     * "GSTIN for a business invoice" field on the shipping step.
     *
     * @return bool
     */
    public function isGstinEnabled(): bool
    {
        return $this->flag('fields/gstin');
    }

    /**
     * Indian mobile number and PIN code rules on addresses in India.
     *
     * @return bool
     */
    public function isIndianValidationEnabled(): bool
    {
        return $this->flag('validation/indian_address');
    }

    /**
     * Free-delivery progress meter in the mini cart and cart.
     *
     * @return bool
     */
    public function isFreeDeliveryMeterEnabled(): bool
    {
        return $this->flag('cart/free_delivery_meter');
    }

    /**
     * Store-scoped flag.
     *
     * @param string $path
     * @return bool
     */
    private function flag(string $path): bool
    {
        return $this->scopeConfig->isSetFlag(self::PREFIX . $path, ScopeInterface::SCOPE_STORE);
    }
}
