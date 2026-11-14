<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\CatalogEnhancement\ViewModel\ProductBadges;
use QuickCart\CustomApi\Api\Data\ProductCardInterface;
use QuickCart\CustomApi\Api\Data\ProductCardInterfaceFactory;
use QuickCart\CustomApi\Api\Data\ProductLabelInterfaceFactory;

/**
 * Turns storefront products into ProductCard data objects, reusing the same label rules,
 * brand and price models as the theme's product cards.
 */
class ProductCardBuilder
{
    /**
     * @param ProductCardInterfaceFactory $cardFactory
     * @param ProductLabelInterfaceFactory $labelFactory
     * @param ProductBadges $badges
     * @param MediaConfig $mediaConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly ProductCardInterfaceFactory $cardFactory,
        private readonly ProductLabelInterfaceFactory $labelFactory,
        private readonly ProductBadges $badges,
        private readonly MediaConfig $mediaConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Cards for a list of products (batch-loads label data first).
     *
     * @param iterable<Product> $products
     * @return ProductCardInterface[]
     */
    public function buildList(iterable $products): array
    {
        $products = is_array($products) ? $products : iterator_to_array($products, false);
        $this->badges->preload($products);

        return array_values(array_map([$this, 'build'], $products));
    }

    /**
     * Card for one product.
     *
     * @param Product $product
     * @return ProductCardInterface
     */
    public function build(Product $product): ProductCardInterface
    {
        $priceInfo = $product->getPriceInfo();
        $regular = (float)$priceInfo->getPrice(RegularPrice::PRICE_CODE)->getAmount()->getValue();
        $final = (float)$priceInfo->getPrice(FinalPrice::PRICE_CODE)->getAmount()->getValue();
        $reviewCount = (int)$product->getData('reviews_count');
        $image = (string)($product->getData('small_image') ?: $product->getData('image'));

        $labels = [];
        foreach ($this->badges->getBadges($product) as $badge) {
            $labels[] = $this->labelFactory->create(['data' => [
                'code' => $badge['code'],
                'text' => $badge['label'],
                'background_color' => $badge['background'],
                'text_color' => $badge['color'],
            ]]);
        }

        return $this->cardFactory->create(['data' => [
            'id' => (int)$product->getId(),
            'sku' => (string)$product->getSku(),
            'name' => (string)$product->getName(),
            'type' => (string)$product->getTypeId(),
            'url' => (string)$product->getProductUrl(),
            'image_url' => $image !== '' && $image !== 'no_selection' ? $this->mediaConfig->getMediaUrl($image) : null,
            'brand' => $this->badges->getBrand($product) ?: null,
            'price' => round(max($regular, $final), 2),
            'final_price' => round($final, 2),
            'discount_percent' => $this->badges->getDiscountPercent($product),
            'currency' => (string)$this->storeManager->getStore()->getCurrentCurrencyCode(),
            'rating_percent' => $reviewCount > 0 ? (int)round((float)$product->getData('rating_summary')) : null,
            'review_count' => $reviewCount,
            'is_in_stock' => (bool)$product->isSalable(),
            'labels' => $labels,
        ]]);
    }
}
