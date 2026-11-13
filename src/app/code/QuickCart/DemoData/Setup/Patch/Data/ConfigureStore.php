<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use Magento\Theme\Model\Theme\Registration as ThemeRegistration;

/**
 * Store settings the storefront copy relies on (free delivery threshold, cash on
 * delivery, flat-rate fee, catalog grid sizes, page titles) and assigns the
 * QuickCart theme.
 */
class ConfigureStore implements DataPatchInterface
{
    public const THEME_PATH = 'frontend/QuickCart/default';
    private const THEME_CONFIG_PATH = 'design/theme/theme_id';

    /**
     * Default-scope configuration.
     */
    private const SETTINGS = [
        'general/store_information/name' => 'QuickCart',
        'carriers/freeshipping/active' => '1',
        'carriers/freeshipping/title' => 'QuickCart Delivery',
        'carriers/freeshipping/name' => 'Free delivery',
        'carriers/freeshipping/free_shipping_subtotal' => '999',
        'carriers/flatrate/active' => '1',
        'carriers/flatrate/title' => 'QuickCart Delivery',
        'carriers/flatrate/name' => 'Standard',
        'carriers/flatrate/type' => 'O',
        'carriers/flatrate/price' => '79',
        'payment/cashondelivery/active' => '1',
        'payment/cashondelivery/title' => 'Cash on Delivery',
        'payment/checkmo/active' => '1',
        'catalog/frontend/grid_per_page_values' => '12,24,36',
        'catalog/frontend/grid_per_page' => '12',
        'catalog/frontend/list_per_page_values' => '10,20,30',
        'catalog/frontend/list_per_page' => '10',
        'catalog/frontend/show_swatches_in_product_list' => '1',
        'design/head/default_title' => 'QuickCart - Shop Smarter. Shop Faster.',
        'design/head/title_suffix' => ' | QuickCart',
        'design/head/default_description' =>
            'QuickCart: smartphones, laptops, audio, wearables, cameras and gaming gear with fast delivery.',
        'design/header/welcome' => 'Free delivery on orders above ₹999',
        'design/footer/copyright' => '© QuickCart. Shop Smarter. Shop Faster.',
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $configWriter
     * @param ThemeRegistration $themeRegistration
     * @param ThemeCollectionFactory $themeCollectionFactory
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly WriterInterface $configWriter,
        private readonly ThemeRegistration $themeRegistration,
        private readonly ThemeCollectionFactory $themeCollectionFactory,
        private readonly IndexerRegistry $indexerRegistry
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
        $this->assignTheme();

        return $this;
    }

    /**
     * Assign the QuickCart theme at every scope that has a theme configured.
     *
     * Themes are normally registered by Magento_Theme's recurring data script,
     * which runs after data patches, so the theme is registered here first.
     *
     * @return void
     */
    private function assignTheme(): void
    {
        $this->themeRegistration->register();
        $theme = $this->themeCollectionFactory->create()->getThemeByFullPath(self::THEME_PATH);
        if (!$theme->getId()) {
            return;
        }

        $connection = $this->moduleDataSetup->getConnection();
        $scopes = $connection->fetchAll(
            $connection->select()
                ->from($this->moduleDataSetup->getTable('core_config_data'), ['scope', 'scope_id'])
                ->where('path = ?', self::THEME_CONFIG_PATH)
        );
        $scopes[] = ['scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 'scope_id' => 0];
        foreach ($scopes as $scope) {
            $this->configWriter->save(
                self::THEME_CONFIG_PATH,
                $theme->getId(),
                $scope['scope'],
                (int)$scope['scope_id']
            );
        }

        $this->indexerRegistry->get('design_config_grid')->reindexAll();
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
