<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Progress towards the Free Shipping carrier's minimum order amount
 * (carriers/freeshipping/free_shipping_subtotal), formatted for the storefront.
 */
class FreeDeliveryProgress
{
    private const XML_PATH_ACTIVE = 'carriers/freeshipping/active';
    private const XML_PATH_SUBTOTAL = 'carriers/freeshipping/free_shipping_subtotal';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * Progress data for a subtotal (base currency, after discounts).
     *
     * @param float $subtotal
     * @param int|null $storeId
     * @return array{enabled: bool, unlocked: bool, percent: int, remaining: float,
     *     remaining_formatted: string, threshold_formatted: string}
     */
    public function calculate(float $subtotal, ?int $storeId = null): array
    {
        $threshold = (float)$this->scopeConfig->getValue(
            self::XML_PATH_SUBTOTAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $enabled = $threshold > 0
            && $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, $storeId);
        $remaining = $enabled ? max(0.0, round($threshold - $subtotal, 2)) : 0.0;

        return [
            'enabled' => $enabled,
            'unlocked' => $enabled && $remaining <= 0.0,
            'percent' => $enabled ? (int)min(100, floor(max(0.0, $subtotal) / $threshold * 100)) : 0,
            'remaining' => $remaining,
            'remaining_formatted' => $this->format($remaining, $storeId),
            'threshold_formatted' => $this->format($threshold, $storeId),
        ];
    }

    /**
     * Plain-text price (no HTML container).
     *
     * @param float $amount
     * @param int|null $storeId
     * @return string
     */
    private function format(float $amount, ?int $storeId): string
    {
        return $this->priceCurrency->format($amount, false, PriceCurrencyInterface::DEFAULT_PRECISION, $storeId);
    }
}
