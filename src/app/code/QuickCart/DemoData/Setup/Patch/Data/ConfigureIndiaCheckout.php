<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;
use QuickCart\DemoData\Model\Fixture;

/**
 * India as the store's home market (checkout defaults, shipping origin, units) and
 * the "Available offers" CMS block shown on the cart page.
 */
class ConfigureIndiaCheckout implements DataPatchInterface
{
    private const SETTINGS = [
        'general/country/default' => 'IN',
        'general/store_information/country_id' => 'IN',
        'shipping/origin/country_id' => 'IN',
        'tax/defaults/country' => 'IN',
        'general/locale/weight_unit' => 'kgs',
        'checkout/options/guest_checkout' => '1',
        'checkout/cart/crosssell_enabled' => '1',
    ];

    /**
     * @param WriterInterface $configWriter
     * @param Fixture $fixture
     * @param BlockRepositoryInterface $blockRepository
     * @param BlockInterfaceFactory $blockFactory
     */
    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly Fixture $fixture,
        private readonly BlockRepositoryInterface $blockRepository,
        private readonly BlockInterfaceFactory $blockFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        foreach (self::SETTINGS as $path => $value) {
            $this->configWriter->save($path, $value);
        }

        try {
            $block = $this->blockRepository->getById('qc-cart-offers');
        } catch (NoSuchEntityException) {
            $block = $this->blockFactory->create();
            $block->setIdentifier('qc-cart-offers');
            $block->setData('stores', [Store::DEFAULT_STORE_ID]);
        }
        $block->setTitle('QuickCart - Cart available offers');
        $block->setIsActive(true);
        $block->setContent($this->fixture->read('cms/qc-cart-offers.html'));
        $this->blockRepository->save($block);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [InstallCartRules::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
