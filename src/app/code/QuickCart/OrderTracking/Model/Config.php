<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Stores > Configuration > QuickCart > Order Tracking.
 */
class Config
{
    private const PREFIX = 'quickcart_tracking/general/';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Storefront tracking page and timeline.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::PREFIX . 'enabled', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Earliest delivery, business days after the order (0..30).
     *
     * @param int|null $storeId
     * @return int
     */
    public function getEtaMinDays(?int $storeId = null): int
    {
        return $this->int('eta_min_days', 0, 30, $storeId);
    }

    /**
     * Latest delivery, business days after the order (0..30, never below the minimum).
     *
     * @param int|null $storeId
     * @return int
     */
    public function getEtaMaxDays(?int $storeId = null): int
    {
        return max($this->getEtaMinDays($storeId), $this->int('eta_max_days', 0, 30, $storeId));
    }

    /**
     * Failed lookups allowed per visitor session in 15 minutes (3..50).
     *
     * @return int
     */
    public function getMaxLookupAttempts(): int
    {
        return $this->int('max_attempts', 3, 50, null);
    }

    /**
     * Store-scoped integer clamped to a range.
     *
     * @param string $field
     * @param int $min
     * @param int $max
     * @param int|null $storeId
     * @return int
     */
    private function int(string $field, int $min, int $max, ?int $storeId): int
    {
        $value = (int)$this->scopeConfig->getValue(self::PREFIX . $field, ScopeInterface::SCOPE_STORE, $storeId);

        return min($max, max($min, $value));
    }
}
