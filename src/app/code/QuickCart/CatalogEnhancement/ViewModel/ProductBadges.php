<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use QuickCart\CatalogEnhancement\Model\Attributes;
use QuickCart\CatalogEnhancement\Model\Config;
use QuickCart\CatalogEnhancement\Model\Label\ProductFacts;
use QuickCart\CatalogEnhancement\Model\Label\Resolver;

/**
 * Product labels and brand for product cards and the product page.
 *
 * Labels come from the rules managed in Admin > QuickCart > Product Labels and are
 * matched against real catalog data (price model, "new" dates, Trending flag, SKUs,
 * category assignments).
 */
class ProductBadges implements ArgumentInterface
{
    /**
     * @param Resolver $resolver
     * @param ProductFacts $facts
     * @param Config $config
     */
    public function __construct(
        private readonly Resolver $resolver,
        private readonly ProductFacts $facts,
        private readonly Config $config
    ) {
    }

    /**
     * Labels for a product, most important first.
     *
     * Each item: id, code (CSS modifier), label (text), style (inline colours).
     *
     * @param Product $product
     * @return array<int, array{id: int, code: string, label: string, style: string}>
     */
    public function getBadges(Product $product): array
    {
        if (!$this->config->isLabelsEnabled()) {
            return [];
        }

        return $this->resolver->resolve($product, $this->config->getMaxLabels());
    }

    /**
     * Batch-load data the label rules need for a list of products.
     *
     * @param iterable<Product> $products
     * @return void
     */
    public function preload(iterable $products): void
    {
        if (!$this->config->isLabelsEnabled() || !$this->resolver->needsCategoryData()) {
            return;
        }
        $ids = [];
        foreach ($products as $product) {
            $ids[] = (int)$product->getId();
        }
        $this->facts->preloadCategoryIds($ids);
    }

    /**
     * Whole-percent discount of the final price versus the regular price.
     *
     * @param Product $product
     * @return int
     */
    public function getDiscountPercent(Product $product): int
    {
        return $this->facts->getDiscountPercent($product);
    }

    /**
     * Brand label (option text of qc_brand), or empty string.
     *
     * @param Product $product
     * @return string
     */
    public function getBrand(Product $product): string
    {
        $value = $product->getData(Attributes::BRAND);
        if ($value === null || $value === '') {
            return '';
        }
        $attribute = $product->getResource()->getAttribute(Attributes::BRAND);
        $source = $attribute ? $attribute->getSource() : null;
        $label = $source instanceof SourceInterface ? $source->getOptionText($value) : '';

        return is_string($label) ? $label : '';
    }
}
