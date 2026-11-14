<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Block\Product;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use QuickCart\ProductRecommendation\Model\Config;
use QuickCart\ProductRecommendation\Model\Recommendation;
use QuickCart\ProductRecommendation\Model\RecommendationProvider;

/**
 * "Frequently bought together" bundle on the product page.
 *
 * Companions come from order co-purchases and manual recommendations, topped up with
 * the product's cross-sells. Only products that can go straight to the cart (simple /
 * virtual, salable, no required options) are offered, because they are added through
 * the product form's native "related_product" field together with the main product.
 */
class BoughtTogether extends AbstractProduct implements IdentityInterface
{
    /**
     * @var Product[]|null
     */
    private ?array $companions = null;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Visibility $visibility
     * @param RecommendationProvider $recommendationProvider
     * @param Config $config
     * @param FormatInterface $localeFormat
     * @param Json $json
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly Visibility $visibility,
        private readonly RecommendationProvider $recommendationProvider,
        private readonly Config $config,
        private readonly FormatInterface $localeFormat,
        private readonly Json $json,
        private readonly PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Companion products, best first.
     *
     * @return Product[]
     */
    public function getCompanions(): array
    {
        if ($this->companions !== null) {
            return $this->companions;
        }
        $this->companions = [];
        $product = $this->getProduct();
        if (!$product || !$product->getId()) {
            return $this->companions;
        }
        $limit = $this->config->getBoughtTogetherLimit();
        $ids = $this->recommendationProvider->getBoughtTogetherIds($product, $limit);
        if (!$ids) {
            return $this->companions;
        }

        $collection = $this->collectionFactory->create();
        $collection->addIdFilter($ids)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('type_id', ['in' => [ProductType::TYPE_SIMPLE, ProductType::TYPE_VIRTUAL]])
            ->addAttributeToFilter('required_options', ['neq' => 1])
            ->setVisibility($this->visibility->getVisibleInCatalogIds());
        $this->_addProductAttributesAndPrices($collection)->addStoreFilter();

        $byId = [];
        foreach ($collection as $item) {
            if ($item->isSaleable()) {
                $byId[(int)$item->getId()] = $item;
            }
        }
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $this->companions[] = $byId[$id];
            }
            if (count($this->companions) >= $limit) {
                break;
            }
        }

        return $this->companions;
    }

    /**
     * Final price amount (incl. configured tax display) used for the bundle total.
     *
     * @param Product $product
     * @return float
     */
    public function getPriceAmount(Product $product): float
    {
        return (float)$product->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getAmount()->getValue();
    }

    /**
     * Formatted final price.
     *
     * @param Product $product
     * @return string
     */
    public function getFormattedPrice(Product $product): string
    {
        return $this->priceCurrency->format($this->getPriceAmount($product), false);
    }

    /**
     * Widget config for QuickCart_ProductRecommendation/js/bought-together.
     *
     * @return string
     */
    public function getJsConfig(): string
    {
        $product = $this->getProduct();

        return $this->json->serialize([
            'basePrice' => $product ? $this->getPriceAmount($product) : 0,
            'priceFormat' => $this->localeFormat->getPriceFormat(),
            'formSelector' => '#product_addtocart_form',
        ]);
    }

    /**
     * Whether the viewed product has options the shopper must pick first.
     *
     * @return bool
     */
    public function isMainProductConfigurable(): bool
    {
        $product = $this->getProduct();

        return $product && ($product->getTypeId() === 'configurable' || (bool)$product->getRequiredOptions());
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        $identities = [[Recommendation::CACHE_TAG]];
        foreach ($this->getCompanions() as $companion) {
            $identities[] = $companion->getIdentities();
        }

        return array_values(array_unique(array_merge(...$identities)));
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        return $this->getCompanions() ? parent::_toHtml() : '';
    }
}
