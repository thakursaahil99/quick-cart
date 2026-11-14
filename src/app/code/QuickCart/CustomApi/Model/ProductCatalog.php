<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\CatalogEnhancement\Model\Attributes;
use QuickCart\CustomApi\Api\Data\AttributeValueInterfaceFactory;
use QuickCart\CustomApi\Api\Data\ProductDetailInterface;
use QuickCart\CustomApi\Api\Data\ProductDetailInterfaceFactory;
use QuickCart\CustomApi\Api\Data\ProductListInterface;
use QuickCart\CustomApi\Api\Data\ProductListInterfaceFactory;
use QuickCart\CustomApi\Api\Data\ProductOptionInterfaceFactory;
use QuickCart\CustomApi\Api\Data\RecommendationsInterface;
use QuickCart\CustomApi\Api\Data\RecommendationsInterfaceFactory;
use QuickCart\CustomApi\Api\ProductCatalogInterface;
use QuickCart\ProductRecommendation\Model\Config as RecommendationConfig;
use QuickCart\ProductRecommendation\Model\RecommendationProvider;

/**
 * Storefront product catalog REST service.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductCatalog implements ProductCatalogInterface
{
    /**
     * @param ProductCollectionLoader $collectionLoader
     * @param ProductCardBuilder $cardBuilder
     * @param ProductQuery $query
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param RecommendationProvider $recommendationProvider
     * @param RecommendationConfig $recommendationConfig
     * @param ProductListInterfaceFactory $listFactory
     * @param ProductDetailInterfaceFactory $detailFactory
     * @param AttributeValueInterfaceFactory $attributeValueFactory
     * @param ProductOptionInterfaceFactory $optionFactory
     * @param RecommendationsInterfaceFactory $recommendationsFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly ProductCollectionLoader $collectionLoader,
        private readonly ProductCardBuilder $cardBuilder,
        private readonly ProductQuery $query,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly RecommendationProvider $recommendationProvider,
        private readonly RecommendationConfig $recommendationConfig,
        private readonly ProductListInterfaceFactory $listFactory,
        private readonly ProductDetailInterfaceFactory $detailFactory,
        private readonly AttributeValueInterfaceFactory $attributeValueFactory,
        private readonly ProductOptionInterfaceFactory $optionFactory,
        private readonly RecommendationsInterfaceFactory $recommendationsFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getList(
        ?int $categoryId = null,
        ?string $brand = null,
        ?float $priceFrom = null,
        ?float $priceTo = null,
        ?string $search = null,
        string $sortBy = 'position',
        string $sortDir = 'asc',
        int $page = 1,
        int $pageSize = 12
    ): ProductListInterface {
        $paging = $this->query->normalize($sortBy, $sortDir, $page, $pageSize, $categoryId !== null);
        $this->query->assertPriceRange($priceFrom, $priceTo);
        $like = $this->query->likePattern($search);

        $collection = $this->collectionLoader->create($like !== null);
        if ($categoryId !== null) {
            $collection->addCategoryFilter($this->getStoreCategory($categoryId));
        }
        if ($brand !== null && trim($brand) !== '') {
            $collection->addAttributeToFilter(Attributes::BRAND, $this->resolveBrandOption($collection, $brand));
        }
        if ($like !== null) {
            $collection->addAttributeToFilter([
                ['attribute' => 'name', 'like' => $like],
                ['attribute' => 'sku', 'like' => $like],
            ]);
        }
        if ($priceFrom !== null) {
            $collection->getSelect()->where('price_index.min_price >= ?', $priceFrom);
        }
        if ($priceTo !== null) {
            $collection->getSelect()->where('price_index.min_price <= ?', $priceTo);
        }

        $collection->addAttributeToSort($paging['attribute'], $paging['direction']);
        $collection->addAttributeToSort('entity_id', $paging['direction']);
        $collection->setPageSize($paging['page_size'])->setCurPage($paging['page']);
        $total = (int)$collection->getSize();
        $items = [];
        // Pages past the end return no items instead of repeating the last page.
        if (($paging['page'] - 1) * $paging['page_size'] < $total) {
            $this->collectionLoader->addReviewSummary($collection);
            $items = $this->cardBuilder->buildList($collection);
        }

        return $this->listFactory->create(['data' => [
            'items' => $items,
            'total_count' => $total,
            'page' => $paging['page'],
            'page_size' => $paging['page_size'],
            'total_pages' => (int)ceil($total / $paging['page_size']),
        ]]);
    }

    /**
     * @inheritdoc
     */
    public function get(string $sku): ProductDetailInterface
    {
        $product = $this->getStorefrontProduct($sku);
        $cards = $this->cardBuilder->buildList($this->collectionLoader->loadByIds([(int)$product->getId()], 1));
        if (!$cards) {
            throw $this->notFound($sku);
        }

        $images = [];
        foreach ($product->getMediaGalleryImages() ?? [] as $image) {
            if (!$image->getDisabled()) {
                $images[] = (string)$image->getUrl();
            }
        }

        return $this->detailFactory->create(['data' => [
            'product' => $cards[0],
            'short_description' => $product->getData('short_description') ?: null,
            'description' => $product->getData('description') ?: null,
            'specifications' => $this->getSpecifications($product),
            'images' => $images,
            'options' => $this->getOptions($product),
            'category_ids' => array_map('intval', $product->getCategoryIds()),
            'related_skus' => $this->linkedSkus($product->getRelatedProducts()),
            'upsell_skus' => $this->linkedSkus($product->getUpSellProducts()),
        ]]);
    }

    /**
     * @inheritdoc
     */
    public function getRecommendations(string $sku): RecommendationsInterface
    {
        $product = $this->getStorefrontProduct($sku);
        $boughtLimit = $this->recommendationConfig->getBoughtTogetherLimit();
        $similarLimit = $this->recommendationConfig->getSimilarLimit();

        $bought = array_filter(
            $this->collectionLoader->loadByIds(
                $this->recommendationProvider->getBoughtTogetherIds($product, $boughtLimit),
                $boughtLimit * 3
            ),
            static fn (Product $item) => $item->isSalable()
        );

        return $this->recommendationsFactory->create(['data' => [
            'sku' => (string)$product->getSku(),
            'bought_together' => $this->cardBuilder->buildList(array_slice(array_values($bought), 0, $boughtLimit)),
            'similar' => $this->cardBuilder->buildList($this->collectionLoader->loadByIds(
                $this->recommendationProvider->getSimilarIds($product, $similarLimit),
                $similarLimit
            )),
        ]]);
    }

    /**
     * Enabled product of this store's website, visible in catalog or search.
     *
     * @param string $sku
     * @return Product
     * @throws NoSuchEntityException
     */
    private function getStorefrontProduct(string $sku): Product
    {
        $store = $this->storeManager->getStore();
        try {
            /** @var Product $product */
            $product = $this->productRepository->get($sku, false, (int)$store->getId());
        } catch (NoSuchEntityException $e) {
            throw $this->notFound($sku);
        }
        if ((int)$product->getStatus() !== Status::STATUS_ENABLED
            || !$product->isVisibleInSiteVisibility()
            || !in_array((int)$store->getWebsiteId(), array_map('intval', $product->getWebsiteIds()), true)) {
            throw $this->notFound($sku);
        }

        return $product;
    }

    /**
     * Active category inside this store's tree.
     *
     * @param int $categoryId
     * @return Category
     * @throws NoSuchEntityException
     */
    private function getStoreCategory(int $categoryId): Category
    {
        $store = $this->storeManager->getStore();
        try {
            /** @var Category $category */
            $category = $this->categoryRepository->get($categoryId, (int)$store->getId());
        } catch (NoSuchEntityException $e) {
            $category = null;
        }
        $rootPath = '1/' . (int)$store->getRootCategoryId() . '/';
        if (!$category || !$category->getIsActive() || !str_starts_with((string)$category->getPath() . '/', $rootPath)) {
            throw new NoSuchEntityException(__('The category with ID "%1" does not exist.', $categoryId));
        }

        return $category;
    }

    /**
     * Brand option ID from a brand name or ID.
     *
     * @param Collection $collection
     * @param string $brand
     * @return int
     * @throws InputException
     */
    private function resolveBrandOption(Collection $collection, string $brand): int
    {
        $attribute = $collection->getResource()->getAttribute(Attributes::BRAND);
        $source = $attribute ? $attribute->getSource() : null;
        $optionId = $source instanceof SourceInterface ? $source->getOptionId(trim($brand)) : null;
        if (!$optionId) {
            throw new InputException(__('Unknown brand "%1".', $brand));
        }

        return (int)$optionId;
    }

    /**
     * Specification attributes that have a value.
     *
     * @param Product $product
     * @return array
     */
    private function getSpecifications(Product $product): array
    {
        $specifications = [];
        foreach (Attributes::SPECIFICATIONS as $code) {
            $attribute = $product->getResource()->getAttribute($code);
            $raw = $product->getData($code);
            if (!$attribute || $raw === null || $raw === '' || $raw === false) {
                continue;
            }
            $value = $attribute->getFrontend()->getValue($product);
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if ((string)$value === '') {
                continue;
            }
            $specifications[] = $this->attributeValueFactory->create(['data' => [
                'code' => $code,
                'label' => (string)$attribute->getStoreLabel(),
                'value' => (string)$value,
            ]]);
        }

        return $specifications;
    }

    /**
     * Configurable options with their value labels.
     *
     * @param Product $product
     * @return array
     */
    private function getOptions(Product $product): array
    {
        if ($product->getTypeId() !== 'configurable') {
            return [];
        }
        $options = [];
        foreach ($product->getTypeInstance()->getConfigurableAttributesAsArray($product) as $attribute) {
            $options[] = $this->optionFactory->create(['data' => [
                'attribute_code' => (string)$attribute['attribute_code'],
                'label' => (string)($attribute['store_label'] ?? $attribute['label'] ?? $attribute['attribute_code']),
                'values' => array_values(array_map(
                    static fn (array $value) => (string)($value['store_label'] ?? $value['label'] ?? ''),
                    $attribute['values'] ?? []
                )),
            ]]);
        }

        return $options;
    }

    /**
     * SKUs of linked products.
     *
     * @param Product[] $products
     * @return string[]
     */
    private function linkedSkus(array $products): array
    {
        return array_values(array_map(static fn (Product $linked) => (string)$linked->getSku(), $products));
    }

    /**
     * Uniform "not found" error (no hint whether the SKU exists but is hidden).
     *
     * @param string $sku
     * @return NoSuchEntityException
     */
    private function notFound(string $sku): NoSuchEntityException
    {
        return new NoSuchEntityException(__('The product with SKU "%1" does not exist.', $sku));
    }
}
