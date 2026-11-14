<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\Label;

use Magento\Catalog\Model\Product;
use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;

/**
 * Decides whether a label rule applies to a product.
 */
class Matcher
{
    /**
     * @param ProductFacts $facts
     * @param Validator $validator
     */
    public function __construct(
        private readonly ProductFacts $facts,
        private readonly Validator $validator
    ) {
    }

    /**
     * Whether the label's condition matches the product.
     *
     * @param LabelInterface $label
     * @param Product $product
     * @return bool
     */
    public function matches(LabelInterface $label, Product $product): bool
    {
        $value = (string)$label->getConditionValue();

        return match ($label->getConditionType()) {
            ConditionType::SALE => $this->facts->getDiscountPercent($product) >= max(1, (int)$value),
            ConditionType::NEW => $this->facts->isNew($product),
            ConditionType::TRENDING => $this->facts->isTrending($product),
            ConditionType::SKU => $this->matchesSku($value, (string)$product->getSku()),
            ConditionType::CATEGORY => $this->matchesCategory($value, $product),
            default => false,
        };
    }

    /**
     * Case-insensitive SKU list match.
     *
     * @param string $value
     * @param string $sku
     * @return bool
     */
    private function matchesSku(string $value, string $sku): bool
    {
        if ($sku === '') {
            return false;
        }
        $skus = array_map('mb_strtolower', $this->validator->splitList($value));

        return in_array(mb_strtolower($sku), $skus, true);
    }

    /**
     * Product is in one of the categories or their descendants.
     *
     * @param string $value
     * @param Product $product
     * @return bool
     */
    private function matchesCategory(string $value, Product $product): bool
    {
        $categories = $this->facts->expandCategoryIds(array_map('intval', $this->validator->splitList($value)));
        if (!$categories) {
            return false;
        }

        return (bool)array_intersect($this->facts->getCategoryIds($product), $categories);
    }
}
