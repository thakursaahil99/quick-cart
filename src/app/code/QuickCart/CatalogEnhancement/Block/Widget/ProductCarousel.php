<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Block\Widget;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Render;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Widget\Block\BlockInterface;
use QuickCart\CatalogEnhancement\Model\ProductSource\SourcePool;
use QuickCart\CatalogEnhancement\ViewModel\ProductBadges;

/**
 * "QuickCart Product Carousel" CMS widget.
 *
 * Renders an animated product rail whose products come from a merchandising source
 * (see SourcePool). Output is block-cached per store/currency/customer group/theme
 * and tagged with product identities for precise full-page-cache invalidation.
 */
class ProductCarousel extends AbstractProduct implements BlockInterface, IdentityInterface
{
    private const DEFAULT_SOURCE = 'trending';
    private const DEFAULT_COUNT = 8;
    private const MAX_COUNT = 24;

    /**
     * @var string
     */
    protected $_template = 'QuickCart_CatalogEnhancement::widget/product-carousel.phtml';

    /**
     * @var Collection|null
     */
    private ?Collection $productCollection = null;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Visibility $productVisibility
     * @param SourcePool $sourcePool
     * @param ProductBadges $badges
     * @param HttpContext $httpContext
     * @param UrlHelper $urlHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly Visibility $productVisibility,
        private readonly SourcePool $sourcePool,
        private readonly ProductBadges $badges,
        private readonly HttpContext $httpContext,
        private readonly UrlHelper $urlHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addData(['cache_lifetime' => 86400]);
    }

    /**
     * Products to show; null when the configured source is unknown.
     *
     * @return Collection|null
     */
    public function getProductCollection(): ?Collection
    {
        if ($this->productCollection !== null) {
            return $this->productCollection;
        }
        try {
            $source = $this->sourcePool->get($this->getSourceCode());
        } catch (NoSuchEntityException $e) {
            $this->_logger->warning($e->getMessage());
            return null;
        }

        $collection = $this->collectionFactory->create();
        $collection->setVisibility($this->productVisibility->getVisibleInCatalogIds());
        $this->_addProductAttributesAndPrices($collection)
            ->addStoreFilter()
            ->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $source->apply($collection);
        $collection->setPageSize($this->getProductsCount())->setCurPage(1);

        $this->productCollection = $collection;

        return $this->productCollection;
    }

    /**
     * Merchandising source code.
     *
     * @return string
     */
    public function getSourceCode(): string
    {
        return (string)($this->getData('source') ?: self::DEFAULT_SOURCE);
    }

    /**
     * Number of products (1..24).
     *
     * @return int
     */
    public function getProductsCount(): int
    {
        $count = (int)$this->getData('products_count');

        return $count > 0 ? min($count, self::MAX_COUNT) : self::DEFAULT_COUNT;
    }

    /**
     * Absolute "View all" URL, or empty string.
     *
     * @return string
     */
    public function getViewAllUrl(): string
    {
        $path = trim((string)$this->getData('view_all_url'), '/ ');

        return $path === '' ? '' : $this->_urlBuilder->getDirectUrl($path);
    }

    /**
     * Badges for a product card.
     *
     * @param Product $product
     * @return array<int, array{code: string, label: string}>
     */
    public function getBadges(Product $product): array
    {
        return $this->badges->getBadges($product);
    }

    /**
     * Brand label for a product card.
     *
     * @param Product $product
     * @return string
     */
    public function getBrand(Product $product): string
    {
        return $this->badges->getBrand($product);
    }

    /**
     * Whether the product can go straight to the cart (no required options).
     *
     * @param Product $product
     * @return bool
     */
    public function canAddToCartFromList(Product $product): bool
    {
        return $product->isSaleable() && $product->getTypeInstance()->isPossibleBuyFromList($product);
    }

    /**
     * Add-to-cart form data (mirrors Magento\Catalog\Block\Product\ListProduct).
     *
     * @param Product $product
     * @return array{action: string, data: array<string, int|string>}
     */
    public function getAddToCartPostParams(Product $product): array
    {
        $url = $this->getAddToCartUrl($product, ['_escape' => false]);

        return [
            'action' => $url,
            'data' => [
                'product' => (int)$product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl($url),
            ],
        ];
    }

    /**
     * Render the final price box; creates the price renderer on CMS pages that lack it.
     *
     * @param Product $product
     * @param string|null $priceType
     * @param string $renderZone
     * @param array $arguments
     * @return string
     */
    public function getProductPriceHtml(
        Product $product,
        $priceType = null,
        $renderZone = Render::ZONE_ITEM_LIST,
        array $arguments = []
    ) {
        $arguments['zone'] = $arguments['zone'] ?? $renderZone;
        $arguments['price_id'] = $arguments['price_id']
            ?? 'qc-price-' . $product->getId() . '-' . $this->getSourceCode();
        $arguments['include_container'] = $arguments['include_container'] ?? true;
        $arguments['display_minimal_price'] = $arguments['display_minimal_price'] ?? true;

        $priceRender = $this->getLayout()->getBlock('product.price.render.default');
        if (!$priceRender) {
            $priceRender = $this->getLayout()->createBlock(
                Render::class,
                'product.price.render.default',
                ['data' => ['price_render_handle' => 'catalog_product_prices']]
            );
        }

        return (string)$priceRender->render(FinalPrice::PRICE_CODE, $product, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function getCacheKeyInfo()
    {
        $store = $this->_storeManager->getStore();

        return [
            'QUICKCART_PRODUCT_CAROUSEL',
            $store->getId(),
            $store->getCurrentCurrencyCode(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP),
            $this->getSourceCode(),
            $this->getProductsCount(),
            (string)$this->getData('title'),
            (string)$this->getData('subtitle'),
            (string)$this->getData('view_all_url'),
            $this->getTemplate(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        $identities = [[Product::CACHE_TAG]];
        $collection = $this->getProductCollection();
        if ($collection) {
            foreach ($collection as $product) {
                $identities[] = $product->getIdentities();
            }
        }

        return array_values(array_unique(array_merge(...$identities)));
    }
}
