<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Stores > Configuration > QuickCart > Recommendations.
 */
class Config
{
    private const PREFIX = 'quickcart_recommendation/';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * "Frequently bought together" block on the product page.
     *
     * @return bool
     */
    public function isBoughtTogetherEnabled(): bool
    {
        return $this->flag('bought_together/enabled');
    }

    /**
     * Companion products shown next to the viewed product (1..4).
     *
     * @return int
     */
    public function getBoughtTogetherLimit(): int
    {
        return $this->int('bought_together/max_items', 1, 4);
    }

    /**
     * Orders placed in the last N days are mined (7..730).
     *
     * @return int
     */
    public function getLookbackDays(): int
    {
        return $this->int('bought_together/lookback_days', 7, 730);
    }

    /**
     * Minimum orders containing both products (1..50).
     *
     * @return int
     */
    public function getMinOrders(): int
    {
        return $this->int('bought_together/min_orders', 1, 50);
    }

    /**
     * Fill the block with cross-sell products when order data is thin.
     *
     * @return bool
     */
    public function isCrossSellFallbackEnabled(): bool
    {
        return $this->flag('bought_together/crosssell_fallback');
    }

    /**
     * "More like this" rail on the product page.
     *
     * @return bool
     */
    public function isSimilarEnabled(): bool
    {
        return $this->flag('similar/enabled');
    }

    /**
     * Products in the similar rail (2..12).
     *
     * @return int
     */
    public function getSimilarLimit(): int
    {
        return $this->int('similar/max_items', 2, 12);
    }

    /**
     * "Recently viewed" rail.
     *
     * @return bool
     */
    public function isRecentlyViewedEnabled(): bool
    {
        return $this->flag('recently_viewed/enabled');
    }

    /**
     * Products in the recently viewed rail (2..12).
     *
     * @return int
     */
    public function getRecentlyViewedLimit(): int
    {
        return $this->int('recently_viewed/max_items', 2, 12);
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

    /**
     * Store-scoped integer clamped to a range.
     *
     * @param string $path
     * @param int $min
     * @param int $max
     * @return int
     */
    private function int(string $path, int $min, int $max): int
    {
        $value = (int)$this->scopeConfig->getValue(self::PREFIX . $path, ScopeInterface::SCOPE_STORE);

        return min($max, max($min, $value));
    }
}
