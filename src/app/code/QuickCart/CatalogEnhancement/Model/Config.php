<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Stores > Configuration > QuickCart > Catalog.
 */
class Config
{
    private const XML_LABELS_ENABLED = 'quickcart_catalog/labels/enabled';
    private const XML_LABELS_MAX = 'quickcart_catalog/labels/max_per_card';
    private const MAX_LABELS_LIMIT = 4;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Whether product labels are shown on the storefront.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isLabelsEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_LABELS_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Maximum labels on one product card (1..4).
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxLabels(?int $storeId = null): int
    {
        $value = (int)$this->scopeConfig->getValue(self::XML_LABELS_MAX, ScopeInterface::SCOPE_STORE, $storeId);

        return min(self::MAX_LABELS_LIMIT, max(1, $value));
    }
}
