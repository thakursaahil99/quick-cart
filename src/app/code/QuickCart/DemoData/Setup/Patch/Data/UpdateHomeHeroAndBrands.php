<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use QuickCart\DemoData\Model\Fixture;

/**
 * Homepage refresh: bento hero (slider with spec chips and product tabs, plus deal
 * tiles) and the "Shop by brand" grid that replaces the brand marquee.
 *
 * Fresh installs already get this content from InstallHomepage (it reads the same
 * fixtures); this patch updates stores installed before the redesign.
 */
class UpdateHomeHeroAndBrands implements DataPatchInterface
{
    /**
     * CMS block identifier => title.
     */
    private const BLOCKS = [
        'qc-home-hero' => 'QuickCart - Home hero slider',
        'qc-home-brands' => 'QuickCart - Home brands',
    ];

    /**
     * @param Fixture $fixture
     * @param BlockRepositoryInterface $blockRepository
     */
    public function __construct(
        private readonly Fixture $fixture,
        private readonly BlockRepositoryInterface $blockRepository
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        foreach (self::BLOCKS as $identifier => $title) {
            $block = $this->blockRepository->getById($identifier);
            $block->setTitle($title);
            $block->setContent($this->fixture->read('cms/' . $identifier . '.html'));
            $this->blockRepository->save($block);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [InstallHomepage::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
