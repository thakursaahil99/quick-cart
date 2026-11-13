<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\CatalogEnhancement\Model\Attributes;
use QuickCart\CatalogEnhancement\Setup\Patch\Data\CreateElectronicsAttributeSet;
use QuickCart\DemoData\Model\AttributeOptionMap;
use QuickCart\DemoData\Model\Fixture;

/**
 * Creates the 48 demo products: simple products and configurable products whose
 * colour variants (qc_color visual swatch) are simple children with their own image.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InstallProducts implements DataPatchInterface
{
    private const QTY = 100;
    private const TAX_CLASS_TAXABLE_GOODS = 2;
    private const IMPORT_DIR = 'import/quickcart/';
    private const DEALS_CATEGORY = 'deals';

    /**
     * @var array<string, array<string, int>>
     */
    private array $options = [];

    /**
     * @var array<string, int>
     */
    private array $categoryIds = [];

    /**
     * @var array<string, string>
     */
    private array $colorLabels = [];

    /**
     * @var int
     */
    private int $attributeSetId = 0;

    /**
     * @var int
     */
    private int $websiteId = 0;

    /**
     * @var WriteInterface|null
     */
    private ?WriteInterface $media = null;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Fixture $fixture
     * @param AttributeOptionMap $optionMap
     * @param ProductInterfaceFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ConfigurableOptionsFactory $configurableOptionsFactory
     * @param StoreManagerInterface $storeManager
     * @param State $appState
     * @param Filesystem $filesystem
     * @param TimezoneInterface $timezone
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly Fixture $fixture,
        private readonly AttributeOptionMap $optionMap,
        private readonly ProductInterfaceFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ConfigurableOptionsFactory $configurableOptionsFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $appState,
        private readonly Filesystem $filesystem,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function (): void {
            $this->install();
        });

        return $this;
    }

    /**
     * Create all products from catalog.json.
     *
     * @return void
     */
    private function install(): void
    {
        $this->websiteId = (int)$this->storeManager->getDefaultStoreView()->getWebsiteId();
        $this->storeManager->setCurrentStore(Store::ADMIN_CODE);
        $this->media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->attributeSetId = (int)$this->eavSetupFactory->create(['setup' => $this->moduleDataSetup])
            ->getAttributeSetId(Product::ENTITY, Attributes::ATTRIBUTE_SET);

        $catalog = $this->fixture->getCatalog();
        foreach ($catalog['colors'] as $key => $color) {
            $this->colorLabels[$key] = $color['label'];
        }
        foreach ([Attributes::BRAND, Attributes::COLOR, Attributes::RAM, Attributes::STORAGE,
                     Attributes::CONNECTIVITY, Attributes::WARRANTY] as $code) {
            $this->options[$code] = $this->optionMap->get($code);
        }
        foreach ($this->categoryCollectionFactory->create()->addAttributeToSelect('url_key') as $category) {
            $this->categoryIds[(string)$category->getData('url_key')] = (int)$category->getId();
        }

        $newRank = 0;
        foreach ($catalog['products'] as $data) {
            if ($this->exists($data['sku'])) {
                continue;
            }
            $newsFrom = null;
            if ($data['is_new']) {
                // Stagger "new from" dates so New Arrivals has a meaningful newest-first order.
                $newsFrom = $this->timezone->date()->modify('-' . (2 * $newRank++) . ' days')->format('Y-m-d');
            }
            $categories = [$this->categoryIds[$data['category']]];
            if ($data['special_price'] !== null && isset($this->categoryIds[self::DEALS_CATEGORY])) {
                $categories[] = $this->categoryIds[self::DEALS_CATEGORY];
            }

            if ($data['type'] === Configurable::TYPE_CODE) {
                $this->createConfigurable($data, $categories, $newsFrom);
            } else {
                $this->saveSimple($data, [
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'url_key' => $data['url_key'],
                    'color' => $data['color'],
                    'image' => $data['image'],
                    'visibility' => Visibility::VISIBILITY_BOTH,
                    'categories' => $categories,
                    'news_from' => $newsFrom,
                ]);
            }
        }
    }

    /**
     * Create the colour children, then the configurable product linking them.
     *
     * @param array $data
     * @param int[] $categories
     * @param string|null $newsFrom
     * @return void
     */
    private function createConfigurable(array $data, array $categories, ?string $newsFrom): void
    {
        $childIds = [];
        $colorIds = [];
        foreach ($data['children'] as $child) {
            $childProduct = $this->exists($child['sku'])
                ? $this->productRepository->get($child['sku'])
                : $this->saveSimple($data, [
                    'sku' => $child['sku'],
                    'name' => $child['name'],
                    'url_key' => $data['url_key'] . '-' . $child['color'],
                    'color' => $child['color'],
                    'image' => $child['image'],
                    'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
                    'categories' => [],
                    'news_from' => null,
                ]);
            $childIds[] = (int)$childProduct->getId();
            $colorIds[] = $this->options[Attributes::COLOR][$this->colorLabels[$child['color']]];
        }

        $product = $this->newProduct(Configurable::TYPE_CODE, $data, [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'url_key' => $data['url_key'],
            'visibility' => Visibility::VISIBILITY_BOTH,
            'categories' => $categories,
            'news_from' => $newsFrom,
        ]);
        $product->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);

        $configurableOptions = $this->configurableOptionsFactory->create([[
            'attribute_id' => $this->optionMap->getAttributeId(Attributes::COLOR),
            'code' => Attributes::COLOR,
            'label' => 'Color',
            'position' => 0,
            'values' => array_map(static fn (int $id): array => ['value_index' => $id], $colorIds),
        ]]);
        $extension = $product->getExtensionAttributes();
        $extension->setConfigurableProductOptions($configurableOptions);
        $extension->setConfigurableProductLinks($childIds);
        $product->setExtensionAttributes($extension);

        $this->attachImage($product, $data['image']);
        $this->productRepository->save($product);
    }

    /**
     * Create and save a simple product.
     *
     * @param array $data shared product data from the fixture
     * @param array $variant sku, name, url_key, color, image, visibility, categories, news_from
     * @return ProductInterface
     */
    private function saveSimple(array $data, array $variant): ProductInterface
    {
        $product = $this->newProduct(Type::TYPE_SIMPLE, $data, $variant);
        $product->setPrice((float)$data['price']);
        $product->setWeight((float)$data['weight']);
        if ($data['special_price'] !== null) {
            $product->setData('special_price', (float)$data['special_price']);
            $product->setData('special_from_date', $this->timezone->date()->format('Y-m-d'));
        }
        if ($variant['color']) {
            $colorLabel = $this->colorLabels[$variant['color']];
            $product->setData(Attributes::COLOR, $this->options[Attributes::COLOR][$colorLabel]);
        }
        $product->setStockData([
            'use_config_manage_stock' => 1,
            'qty' => self::QTY,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ]);
        $this->attachImage($product, $variant['image']);

        return $this->productRepository->save($product);
    }

    /**
     * Product with the data shared by every type.
     *
     * @param string $typeId
     * @param array $data
     * @param array $variant
     * @return Product
     */
    private function newProduct(string $typeId, array $data, array $variant): Product
    {
        /** @var Product $product */
        $product = $this->productFactory->create();
        $product->setStoreId(Store::DEFAULT_STORE_ID)
            ->setTypeId($typeId)
            ->setAttributeSetId($this->attributeSetId)
            ->setSku($variant['sku'])
            ->setName($variant['name'])
            ->setUrlKey($variant['url_key'])
            ->setWebsiteIds([$this->websiteId])
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility($variant['visibility'])
            ->setCategoryIds($variant['categories'])
            ->setData('tax_class_id', self::TAX_CLASS_TAXABLE_GOODS)
            ->setData('description', $data['description'])
            ->setData('short_description', $data['short_description'])
            ->setData('meta_title', $variant['name'])
            ->setData('meta_description', $data['meta_description']);

        if ($variant['news_from']) {
            $product->setData('news_from_date', $variant['news_from']);
        }
        $product->setData(Attributes::IS_TRENDING, $data['trending'] ? 1 : 0);
        $product->setData(Attributes::BRAND, $this->options[Attributes::BRAND][$data['brand']] ?? null);
        $selects = [Attributes::RAM => 'ram', Attributes::STORAGE => 'storage', Attributes::WARRANTY => 'warranty'];
        foreach ($selects as $code => $key) {
            if (!empty($data[$key])) {
                $product->setData($code, $this->options[$code][$data[$key]] ?? null);
            }
        }
        if (!empty($data['connectivity'])) {
            $ids = array_filter(array_map(
                fn (string $label): ?int => $this->options[Attributes::CONNECTIVITY][$label] ?? null,
                $data['connectivity']
            ));
            $product->setData(Attributes::CONNECTIVITY, implode(',', $ids));
        }
        if (!empty($data['display'])) {
            $product->setData(Attributes::DISPLAY, $data['display']);
        }
        if (!empty($data['battery'])) {
            $product->setData(Attributes::BATTERY, $data['battery']);
        }

        return $product;
    }

    /**
     * Copy a fixture image into pub/media and add it as base, small and thumbnail image.
     *
     * @param Product $product
     * @param string $image fixture path relative to fixtures/media
     * @return void
     */
    private function attachImage(Product $product, string $image): void
    {
        $target = self::IMPORT_DIR . substr((string)strrchr('/' . $image, '/'), 1);
        if (!$this->media->isExist($target)) {
            $this->media->writeFile($target, $this->fixture->read('media/' . $image));
        }
        $product->addImageToMediaGallery(
            $this->media->getAbsolutePath($target),
            ['image', 'small_image', 'thumbnail'],
            false,
            false
        );
    }

    /**
     * Whether a product with the SKU exists.
     *
     * @param string $sku
     * @return bool
     */
    private function exists(string $sku): bool
    {
        try {
            $this->productRepository->get($sku);
            return true;
        } catch (NoSuchEntityException) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [
            CreateElectronicsAttributeSet::class,
            InstallAttributeOptions::class,
            InstallCategories::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
