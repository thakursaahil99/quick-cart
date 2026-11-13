<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Block\Widget;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

/**
 * "QuickCart Category Tiles" CMS widget: top-level menu categories with their
 * category image and product count.
 */
class CategoryTiles extends Template implements BlockInterface, IdentityInterface
{
    private const DEFAULT_LIMIT = 8;

    /**
     * @var string
     */
    protected $_template = 'QuickCart_CatalogEnhancement::widget/category-tiles.phtml';

    /**
     * @var Collection|null
     */
    private ?Collection $categories = null;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $categoryCollectionFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly CollectionFactory $categoryCollectionFactory,
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
     * Active level-2 categories of the store root that are included in the menu.
     *
     * @return Collection
     */
    public function getCategories(): Collection
    {
        if ($this->categories === null) {
            $store = $this->_storeManager->getStore();
            $collection = $this->categoryCollectionFactory->create();
            $collection->setStoreId((int)$store->getId())
                ->addAttributeToSelect(['name', 'image', 'url_key', 'url_path'])
                ->addAttributeToFilter('parent_id', (int)$store->getRootCategoryId())
                ->addAttributeToFilter('include_in_menu', 1)
                ->addIsActiveFilter()
                ->addUrlRewriteToResult()
                ->setLoadProductCount(true)
                ->setOrder('position', Collection::SORT_ORDER_ASC)
                ->setPageSize($this->getLimit());
            $this->categories = $collection;
        }

        return $this->categories;
    }

    /**
     * Max tiles.
     *
     * @return int
     */
    public function getLimit(): int
    {
        $limit = (int)$this->getData('limit');

        return $limit > 0 ? min($limit, 24) : self::DEFAULT_LIMIT;
    }

    /**
     * Category image URL or empty string.
     *
     * @param Category $category
     * @return string
     */
    public function getImageUrl(Category $category): string
    {
        return (string)$category->getImageUrl();
    }

    /**
     * @inheritdoc
     */
    public function getCacheKeyInfo()
    {
        return [
            'QUICKCART_CATEGORY_TILES',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->getLimit(),
            (string)$this->getData('title'),
            (string)$this->getData('subtitle'),
            $this->getTemplate(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        $identities = [Category::CACHE_TAG];
        foreach ($this->getCategories() as $category) {
            $identities[] = Category::CACHE_TAG . '_' . $category->getId();
        }

        return $identities;
    }
}
