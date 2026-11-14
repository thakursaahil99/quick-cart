<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductLinkManagementInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\DemoData\Model\Fixture;

/**
 * Related ("Pairs well with"), up-sell ("Upgrade your pick") and cross-sell
 * ("Complete your setup", shown in the cart) product links.
 */
class InstallProductLinks implements DataPatchInterface
{
    private const TYPES = ['related', 'upsell', 'crosssell'];

    /**
     * @param Fixture $fixture
     * @param ProductLinkManagementInterface $linkManagement
     * @param ProductLinkInterfaceFactory $linkFactory
     * @param StoreManagerInterface $storeManager
     * @param State $appState
     */
    public function __construct(
        private readonly Fixture $fixture,
        private readonly ProductLinkManagementInterface $linkManagement,
        private readonly ProductLinkInterfaceFactory $linkFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $appState
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function (): void {
            $this->storeManager->setCurrentStore(Store::ADMIN_CODE);
            $products = $this->fixture->getCatalog()['products'];
            $types = array_column($products, 'type', 'sku');

            foreach ($products as $data) {
                $links = [];
                foreach (self::TYPES as $type) {
                    foreach ($data['links'][$type] ?? [] as $position => $linkedSku) {
                        $link = $this->linkFactory->create();
                        $link->setSku($data['sku'])
                            ->setLinkType($type)
                            ->setLinkedProductSku($linkedSku)
                            ->setLinkedProductType($types[$linkedSku] ?? 'simple')
                            ->setPosition($position + 1);
                        $links[] = $link;
                    }
                }
                if ($links) {
                    $this->linkManagement->setProductLinks($data['sku'], $links);
                }
            }
        });

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [InstallProducts::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
