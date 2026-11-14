<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use QuickCart\SalesDashboard\Model\Report\Dashboard;
use QuickCart\SalesDashboard\Model\Report\Money;
use QuickCart\SalesDashboard\Model\Report\Period;
use QuickCart\SalesDashboard\Model\Report\Reports;
use QuickCart\SalesDashboard\Model\Source\RangePreset;

/**
 * Request-scoped analytics for the dashboard and report templates: the selected period
 * (preset or custom range from the query string) and admin URLs.
 */
class Analytics implements ArgumentInterface
{
    private const XML_DEFAULT_RANGE = 'quickcart_dashboard/general/default_range';
    private const XML_TOP_PRODUCTS = 'quickcart_dashboard/general/top_products';

    /**
     * @var array|null
     */
    private ?array $period = null;

    /**
     * @var array|null
     */
    private ?array $dashboard = null;

    /**
     * @param RequestInterface $request
     * @param Period $periodResolver
     * @param Dashboard $dashboardBuilder
     * @param RangePreset $presets
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param Json $json
     * @param Reports $reports
     * @param Money $money
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Period $periodResolver,
        private readonly Dashboard $dashboardBuilder,
        private readonly RangePreset $presets,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly UrlInterface $urlBuilder,
        private readonly Json $json,
        private readonly Reports $reports,
        private readonly Money $money
    ) {
    }

    /**
     * Date-wise report for the selected period.
     *
     * @return array
     */
    public function getDailyReport(): array
    {
        return $this->reports->daily($this->getPeriod());
    }

    /**
     * Best-selling products for the selected period.
     *
     * @return array
     */
    public function getProductReport(): array
    {
        return $this->reports->products($this->getPeriod());
    }

    /**
     * Full amount in a currency.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function formatMoney(float $amount, string $currency): string
    {
        return $this->money->format($amount, $currency);
    }

    /**
     * Selected period.
     *
     * @return array
     */
    public function getPeriod(): array
    {
        if ($this->period === null) {
            $default = (string)$this->scopeConfig->getValue(self::XML_DEFAULT_RANGE) ?: RangePreset::LAST_30;
            $this->period = $this->periodResolver->resolve(
                (string)($this->request->getParam('range') ?: $default),
                $this->request->getParam('from') ? (string)$this->request->getParam('from') : null,
                $this->request->getParam('to') ? (string)$this->request->getParam('to') : null,
                $this->dashboardBuilder->today(),
                array_key_exists($default, $this->presets->getPresets()) ? $default : RangePreset::LAST_30
            );
        }

        return $this->period;
    }

    /**
     * Dashboard data for the selected period.
     *
     * @return array
     */
    public function getDashboard(): array
    {
        if ($this->dashboard === null) {
            $top = (int)$this->scopeConfig->getValue(self::XML_TOP_PRODUCTS);
            $this->dashboard = $this->dashboardBuilder->build($this->getPeriod(), min(15, max(3, $top ?: 6)));
        }

        return $this->dashboard;
    }

    /**
     * Range presets (value => label).
     *
     * @return array<string, string>
     */
    public function getPresets(): array
    {
        return $this->presets->getPresets();
    }

    /**
     * URL of the current page for a preset.
     *
     * @param string $preset
     * @return string
     */
    public function getPresetUrl(string $preset): string
    {
        return $this->urlBuilder->getUrl('*/*/*', ['_query' => ['range' => $preset]]);
    }

    /**
     * Admin URL with the current period in the query string.
     *
     * @param string $route
     * @param array $query
     * @return string
     */
    public function getPeriodUrl(string $route, array $query = []): string
    {
        $period = $this->getPeriod();
        $base = $period['preset'] === RangePreset::CUSTOM
            ? ['range' => RangePreset::CUSTOM, 'from' => $period['from'], 'to' => $period['to']]
            : ['range' => $period['preset']];

        return $this->urlBuilder->getUrl($route, ['_query' => $base + $query]);
    }

    /**
     * Admin order view URL.
     *
     * @param int $orderId
     * @return string
     */
    public function getOrderUrl(int $orderId): string
    {
        return $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $orderId]);
    }

    /**
     * Admin URL.
     *
     * @param string $route
     * @return string
     */
    public function getUrl(string $route): string
    {
        return $this->urlBuilder->getUrl($route);
    }

    /**
     * JSON for the chart hover layer.
     *
     * @param array $data
     * @return string
     */
    public function toJson(array $data): string
    {
        return (string)$this->json->serialize($data);
    }
}
