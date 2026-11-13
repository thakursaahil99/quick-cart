<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;
use QuickCart\DemoData\Model\Fixture;

/**
 * Homepage content as editable CMS blocks (hero, trust bar, promos, brands) plus
 * the "home" CMS page that composes them with QuickCart widgets.
 */
class InstallHomepage implements DataPatchInterface
{
    /**
     * CMS block identifier => title.
     */
    private const BLOCKS = [
        'qc-home-hero' => 'QuickCart - Home hero slider',
        'qc-home-trust' => 'QuickCart - Home trust bar',
        'qc-home-promos' => 'QuickCart - Home promo banners',
        'qc-home-brands' => 'QuickCart - Home brand marquee',
        'qc-footer-shop' => 'QuickCart - Footer shop links',
    ];

    /**
     * @param Fixture $fixture
     * @param BlockRepositoryInterface $blockRepository
     * @param BlockInterfaceFactory $blockFactory
     * @param PageRepositoryInterface $pageRepository
     * @param GetPageByIdentifierInterface $getPageByIdentifier
     */
    public function __construct(
        private readonly Fixture $fixture,
        private readonly BlockRepositoryInterface $blockRepository,
        private readonly BlockInterfaceFactory $blockFactory,
        private readonly PageRepositoryInterface $pageRepository,
        private readonly GetPageByIdentifierInterface $getPageByIdentifier
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        foreach (self::BLOCKS as $identifier => $title) {
            try {
                $block = $this->blockRepository->getById($identifier);
            } catch (NoSuchEntityException) {
                $block = $this->blockFactory->create();
                $block->setIdentifier($identifier);
                $block->setData('stores', [Store::DEFAULT_STORE_ID]);
            }
            $block->setTitle($title);
            $block->setIsActive(true);
            $block->setContent($this->fixture->read('cms/' . $identifier . '.html'));
            $this->blockRepository->save($block);
        }

        $page = $this->getPageByIdentifier->execute('home', Store::DEFAULT_STORE_ID);
        $page->setTitle('QuickCart - Shop Smarter. Shop Faster.');
        $page->setContentHeading('');
        $page->setPageLayout('1column');
        $page->setContent($this->fixture->read('cms/home.html'));
        $page->setMetaDescription(
            'Shop smartphones, laptops, audio, wearables, cameras and gaming gear at QuickCart. '
            . 'Free delivery above ₹999.'
        );
        $this->pageRepository->save($page);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [InstallCategories::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
