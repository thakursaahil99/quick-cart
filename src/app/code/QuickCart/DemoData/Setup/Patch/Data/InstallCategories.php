<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\DemoData\Model\Fixture;

/**
 * Creates the category tree (anchor categories, shown in the menu) with tile images.
 */
class InstallCategories implements DataPatchInterface
{
    private const IMAGE_DIR = 'catalog/category/';

    /**
     * @param Fixture $fixture
     * @param CategoryInterfaceFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param State $appState
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly Fixture $fixture,
        private readonly CategoryInterfaceFactory $categoryFactory,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CollectionFactory $categoryCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $appState,
        private readonly Filesystem $filesystem
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
     * Create the tree under the default store's root category.
     *
     * @return void
     */
    private function install(): void
    {
        $rootId = (int)$this->storeManager->getDefaultStoreView()->getRootCategoryId();
        $this->storeManager->setCurrentStore(Store::ADMIN_CODE);
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

        foreach ($this->fixture->getCatalog()['categories'] as $position => $data) {
            $image = substr((string)strrchr('/' . $data['image'], '/'), 1);
            $media->writeFile(self::IMAGE_DIR . $image, $this->fixture->read('media/' . $data['image']));

            $parent = $this->createCategory($rootId, $position + 1, [
                'name' => $data['name'],
                'url_key' => $data['url_key'],
                'description' => $data['description'],
                'image' => $image,
            ]);
            foreach ($data['children'] as $childPosition => $child) {
                $this->createCategory((int)$parent->getId(), $childPosition + 1, [
                    'name' => $child['name'],
                    'url_key' => $child['url_key'],
                ]);
            }
        }
    }

    /**
     * Create a category unless one with the same URL key exists under the parent.
     *
     * @param int $parentId
     * @param int $position
     * @param array $data name, url_key and optional description/image
     * @return CategoryInterface
     */
    private function createCategory(int $parentId, int $position, array $data): CategoryInterface
    {
        $existing = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('parent_id', $parentId)
            ->addAttributeToFilter('url_key', $data['url_key'])
            ->setPageSize(1)
            ->getFirstItem();
        if ($existing->getId()) {
            return $existing;
        }

        /** @var Category $category */
        $category = $this->categoryFactory->create();
        $category->setStoreId(Store::DEFAULT_STORE_ID);
        $category->setParentId($parentId);
        $category->setName($data['name']);
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);
        $category->setPosition($position);
        $category->setData('url_key', $data['url_key']);
        $category->setData('is_anchor', 1);
        $category->setData('display_mode', Category::DM_PRODUCT);
        if (!empty($data['description'])) {
            $category->setData('description', $data['description']);
        }
        if (!empty($data['image'])) {
            $category->setData('image', $data['image']);
        }

        return $this->categoryRepository->save($category);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
