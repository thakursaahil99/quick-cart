<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Plugin\CustomerData;

use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use QuickCart\CheckoutEnhancement\Model\Config;
use QuickCart\CheckoutEnhancement\Model\FreeDeliveryProgress;

/**
 * Adds "qc_free_delivery" to the private "cart" customer-data section, so the
 * mini cart and cart page meters update with every cart change without extra
 * requests and stay correct on full-page-cached pages.
 */
class AddFreeDeliveryProgress
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param FreeDeliveryProgress $progress
     * @param Config $config
     */
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly FreeDeliveryProgress $progress,
        private readonly Config $config
    ) {
    }

    /**
     * After plugin for getSectionData().
     *
     * @param Cart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(Cart $subject, array $result): array
    {
        if (!$this->config->isFreeDeliveryMeterEnabled()) {
            $result['qc_free_delivery'] = ['enabled' => false, 'unlocked' => false, 'percent' => 0];

            return $result;
        }
        $quote = $this->checkoutSession->getQuote();
        $result['qc_free_delivery'] = $this->progress->calculate(
            (float)$quote->getBaseSubtotalWithDiscount(),
            (int)$quote->getStoreId() ?: null
        );

        return $result;
    }
}
