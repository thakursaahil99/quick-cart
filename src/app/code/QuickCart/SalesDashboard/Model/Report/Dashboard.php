<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\Report;

use DateTimeImmutable;
use DateTimeZone;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\SalesDashboard\Api\SalesTargetRepositoryInterface;
use QuickCart\SalesDashboard\Model\Chart\ChartGeometry;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesAnalytics;

/**
 * Builds everything the sales dashboard shows for a period, already formatted and laid out.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Dashboard
{
    /**
     * Order states grouped for the status mix; colours follow the group, never its rank
     * (validated categorical slots 1-5).
     */
    private const STATUS_GROUPS = [
        'delivered' => ['label' => 'Delivered', 'color' => '#2a78d6', 'ink' => '#ffffff', 'states' => [Order::STATE_COMPLETE]],
        'in_progress' => ['label' => 'In progress', 'color' => '#eb6834', 'ink' => '#ffffff', 'states' => [Order::STATE_PROCESSING]],
        'pending' => ['label' => 'Pending', 'color' => '#1baf7a', 'ink' => '#ffffff', 'states' => [
            Order::STATE_NEW, Order::STATE_PENDING_PAYMENT, Order::STATE_PAYMENT_REVIEW, Order::STATE_HOLDED,
        ]],
        'canceled' => ['label' => 'Cancelled', 'color' => '#eda100', 'ink' => '#0b0b0b', 'states' => [Order::STATE_CANCELED]],
        'refunded' => ['label' => 'Refunded', 'color' => '#e87ba4', 'ink' => '#0b0b0b', 'states' => [Order::STATE_CLOSED]],
    ];

    private const CHART_WIDTH = 760;
    private const CHART_HEIGHT = 240;
    private const ORDERS_HEIGHT = 150;
    private const PLOT_LEFT = 64;
    private const PLOT_RIGHT = 14;
    private const PLOT_TOP = 12;
    private const PLOT_BOTTOM = 26;

    /**
     * @param SalesAnalytics $analytics
     * @param Period $period
     * @param Money $money
     * @param ChartGeometry $geometry
     * @param SalesTargetRepositoryInterface $targetRepository
     * @param TimezoneInterface $timezone
     * @param StoreManagerInterface $storeManager
     * @param OrderConfig $orderConfig
     * @param ScopeConfigInterface $scopeConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly SalesAnalytics $analytics,
        private readonly Period $period,
        private readonly Money $money,
        private readonly ChartGeometry $geometry,
        private readonly SalesTargetRepositoryInterface $targetRepository,
        private readonly TimezoneInterface $timezone,
        private readonly StoreManagerInterface $storeManager,
        private readonly OrderConfig $orderConfig,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Today in the store timezone.
     *
     * @return DateTimeImmutable
     */
    public function today(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->timezone->date())->setTime(0, 0);
    }

    /**
     * Base currency code.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return (string)$this->storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * UTC timestamps covering whole store-local days.
     *
     * @param string $fromDay
     * @param string $toDay
     * @return array{0: string, 1: string}
     */
    public function utcRange(string $fromDay, string $toDay): array
    {
        return [
            $this->timezone->convertConfigTimeToUtc($fromDay . ' 00:00:00'),
            $this->timezone->convertConfigTimeToUtc($toDay . ' 23:59:59'),
        ];
    }

    /**
     * Store timezone offset (+05:30).
     *
     * @return string
     */
    public function utcOffset(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone($this->timezone->getConfigTimezone())))->format('P');
    }

    /**
     * Full dashboard data for a resolved period (see Period::resolve()).
     *
     * @param array $period
     * @param int $topProducts
     * @return array
     */
    public function build(array $period, int $topProducts): array
    {
        $currency = $this->getCurrency();
        [$from, $to] = $this->utcRange($period['from'], $period['to']);
        [$prevFrom, $prevTo] = $this->utcRange($period['prev_from'], $period['prev_to']);

        $now = $this->analytics->getTotals($from, $to);
        $before = $this->analytics->getTotals($prevFrom, $prevTo);
        $daily = $this->dailySeries($period['from'], $period['to'], $from, $to);
        $newCustomers = $this->analytics->countNewCustomers($from, $to);
        $newCustomersBefore = $this->analytics->countNewCustomers($prevFrom, $prevTo);
        $paid = max(0, $now['orders'] - $now['canceled']);
        $paidBefore = max(0, $before['orders'] - $before['canceled']);
        $aov = $paid ? $now['revenue'] / $paid : 0.0;
        $aovBefore = $paidBefore ? $before['revenue'] / $paidBefore : 0.0;
        $cancelRate = $now['orders'] ? $now['canceled'] / $now['orders'] * 100 : 0.0;
        $cancelRateBefore = $before['orders'] ? $before['canceled'] / $before['orders'] * 100 : 0.0;
        $compare = (string)__('vs previous %1 days', $period['days']);

        $revenueSeries = array_column($daily, 'revenue');
        $ordersSeries = array_column($daily, 'orders');

        return [
            'period' => $period,
            'currency' => $currency,
            'compare_label' => $compare,
            'hero' => [
                'label' => (string)__('Net revenue'),
                'value' => $this->money->format($now['revenue'], $currency),
                'delta' => $this->delta($this->money->change($now['revenue'], $before['revenue']), true),
            ],
            'tiles' => [
                $this->tile(__('Orders'), number_format($now['orders']), $this->money->change($now['orders'], $before['orders']), true, $ordersSeries),
                $this->tile(__('Average order value'), $this->money->compact($aov, $currency), $this->money->change($aov, $aovBefore), true, null),
                $this->tile(__('Items sold'), number_format($now['items']), $this->money->change($now['items'], $before['items']), true, array_column($daily, 'items')),
                $this->tile(__('Customers who ordered'), number_format($now['customers']), $this->money->change($now['customers'], $before['customers']), true, null),
                $this->tile(__('New accounts'), number_format($newCustomers), $this->money->change($newCustomers, $newCustomersBefore), true, null),
                $this->tile(
                    __('Cancellation rate'),
                    number_format($cancelRate, 1) . '%',
                    $before['orders'] ? round($cancelRate - $cancelRateBefore, 1) : null,
                    false,
                    null,
                    true
                ),
            ],
            'revenue_chart' => $this->revenueChart($daily, $revenueSeries, $currency),
            'orders_chart' => $this->ordersChart($daily, $ordersSeries),
            'status' => $this->statusMix($this->analytics->getStates($from, $to)),
            'top_products' => $this->topProducts($this->analytics->getTopProducts($from, $to, $topProducts), $currency),
            'payments' => $this->payments($this->analytics->getPaymentMethods($from, $to), $currency),
            'recent' => $this->recentOrders($this->analytics->getRecentOrders(8), $currency),
            'target' => $this->targetProgress($currency),
            'discount' => $this->money->format($now['discount'], $currency),
        ];
    }

    /**
     * Zero-filled per-day rows for the period.
     *
     * @param string $fromDay
     * @param string $toDay
     * @param string $fromUtc
     * @param string $toUtc
     * @return array<string, array{day: string, orders: int, canceled: int, revenue: float, items: float, discount: float, shipping: float}>
     */
    public function dailySeries(string $fromDay, string $toDay, string $fromUtc, string $toUtc): array
    {
        $rows = $this->analytics->getDaily($fromUtc, $toUtc, $this->utcOffset());
        $series = [];
        foreach ($this->period->days($fromDay, $toDay) as $day) {
            $series[$day] = ['day' => $day] + ($rows[$day] ?? [
                'orders' => 0, 'canceled' => 0, 'revenue' => 0.0, 'items' => 0.0, 'discount' => 0.0, 'shipping' => 0.0,
            ]);
        }

        return $series;
    }

    /**
     * Stat tile with optional change and sparkline.
     *
     * @param \Magento\Framework\Phrase $label
     * @param string $value
     * @param float|null $change Percent (or points when $isPoints)
     * @param bool $upIsGood
     * @param array|null $spark
     * @param bool $isPoints
     * @return array
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function tile(
        \Magento\Framework\Phrase $label,
        string $value,
        ?float $change,
        bool $upIsGood,
        ?array $spark,
        bool $isPoints = false
    ): array {
        $sparkline = null;
        if ($spark && count($spark) > 1) {
            $top = max(1.0, (float)max($spark));
            $sparkline = $this->geometry->line($spark, ['x' => 1, 'y' => 3, 'width' => 118, 'height' => 28], $top);
        }

        return [
            'label' => (string)$label,
            'value' => $value,
            'delta' => $this->delta($change, $upIsGood, $isPoints),
            'spark' => $sparkline,
        ];
    }

    /**
     * Change badge data: text, direction and whether it is good news.
     *
     * @param float|null $change
     * @param bool $upIsGood
     * @param bool $isPoints
     * @return array{text: string, direction: string, tone: string}|null
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function delta(?float $change, bool $upIsGood, bool $isPoints = false): ?array
    {
        if ($change === null) {
            return null;
        }
        $direction = abs($change) < 0.05 ? 'flat' : ($change > 0 ? 'up' : 'down');
        $tone = $direction === 'flat' ? 'neutral' : (($direction === 'up') === $upIsGood ? 'good' : 'bad');

        return [
            'text' => sprintf('%s%s%s', $change > 0 ? '+' : '', number_format($change, 1), $isPoints ? ' pts' : '%'),
            'direction' => $direction,
            'tone' => $tone,
        ];
    }

    /**
     * Revenue line with area wash, y ticks and x labels.
     *
     * @param array $daily
     * @param float[] $values
     * @param string $currency
     * @return array
     */
    private function revenueChart(array $daily, array $values, string $currency): array
    {
        $box = $this->plotBox(self::CHART_HEIGHT);
        $ticks = $this->geometry->niceTicks((float)max([0.0, ...$values]));
        $top = (float)end($ticks);
        $line = $this->geometry->line($values, $box, $top);
        $days = array_keys($daily);
        $points = [];
        foreach ($line['points'] as $index => $point) {
            $points[] = $point + [
                'label' => $this->dayLabel($days[$index]),
                'value' => $this->money->format($values[$index], $currency),
                'orders' => $daily[$days[$index]]['orders'],
            ];
        }

        return [
            'width' => self::CHART_WIDTH,
            'height' => self::CHART_HEIGHT,
            'box' => $box,
            'line' => $line['line'],
            'area' => $line['area'],
            'points' => $points,
            'ticks' => $this->tickRows($ticks, $box, $top, fn (float $v) => $this->money->compact($v, $currency)),
            'x_labels' => $this->xLabels($days, array_column($line['points'], 'x')),
            'total' => $this->money->format(array_sum($values), $currency),
        ];
    }

    /**
     * Orders per day as columns.
     *
     * @param array $daily
     * @param int[] $values
     * @return array
     */
    private function ordersChart(array $daily, array $values): array
    {
        $box = $this->plotBox(self::ORDERS_HEIGHT);
        $ticks = $this->geometry->niceTicks((float)max([0, ...$values]), 3);
        $top = (float)end($ticks);
        $days = array_keys($daily);
        $columns = [];
        foreach ($this->geometry->columns($values, $box, $top) as $index => $column) {
            $columns[] = $column + [
                'label' => $this->dayLabel($days[$index]),
                'orders' => $daily[$days[$index]]['orders'],
                'canceled' => $daily[$days[$index]]['canceled'],
            ];
        }

        return [
            'width' => self::CHART_WIDTH,
            'height' => self::ORDERS_HEIGHT,
            'box' => $box,
            'columns' => $columns,
            'ticks' => $this->tickRows($ticks, $box, $top, static fn (float $v) => number_format($v)),
            'x_labels' => $this->xLabels($days, array_column($columns, 'center')),
        ];
    }

    /**
     * Orders per status group with shares (segmented bar).
     *
     * @param array<string, array{orders: int, revenue: float}> $states
     * @return array{total: int, segments: array}
     */
    private function statusMix(array $states): array
    {
        $total = array_sum(array_column($states, 'orders'));
        $segments = [];
        foreach (self::STATUS_GROUPS as $key => $group) {
            $orders = 0;
            foreach ($group['states'] as $state) {
                $orders += $states[$state]['orders'] ?? 0;
            }
            if ($orders > 0) {
                $segments[] = [
                    'key' => $key,
                    'label' => (string)__($group['label']),
                    'orders' => $orders,
                    'share' => round($orders / $total * 100, 1),
                    'color' => $group['color'],
                    'ink' => $group['ink'],
                ];
            }
        }

        return ['total' => $total, 'segments' => $segments];
    }

    /**
     * Top products with bar lengths relative to the best seller.
     *
     * @param array $rows
     * @param string $currency
     * @return array
     */
    private function topProducts(array $rows, string $currency): array
    {
        $max = $rows ? max(array_column($rows, 'revenue')) : 0.0;

        return array_map(fn (array $row) => $row + [
            'revenue_label' => $this->money->compact($row['revenue'], $currency),
            'revenue_full' => $this->money->format($row['revenue'], $currency),
            'bar' => $max > 0 ? round($row['revenue'] / $max * 100, 1) : 0.0,
        ], $rows);
    }

    /**
     * Payment methods with titles and shares.
     *
     * @param array<string, array{orders: int, revenue: float}> $methods
     * @param string $currency
     * @return array
     */
    private function payments(array $methods, string $currency): array
    {
        $total = array_sum(array_column($methods, 'orders'));
        $rows = [];
        foreach ($methods as $code => $row) {
            $rows[] = [
                'code' => $code,
                'label' => (string)($this->scopeConfig->getValue('payment/' . $code . '/title') ?: $code),
                'orders' => $row['orders'],
                'share' => $total ? round($row['orders'] / $total * 100, 1) : 0.0,
                'revenue' => $this->money->compact($row['revenue'], $currency),
            ];
        }

        return $rows;
    }

    /**
     * Latest orders for the table.
     *
     * @param array $rows
     * @param string $currency
     * @return array
     */
    private function recentOrders(array $rows, string $currency): array
    {
        return array_map(fn (array $row) => [
            'id' => (int)$row['entity_id'],
            'number' => (string)$row['increment_id'],
            'date' => $this->timezone->formatDateTime($row['created_at'], \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT),
            'customer' => trim($row['customer_firstname'] . ' ' . $row['customer_lastname']) ?: (string)__('Guest'),
            'guest' => (bool)$row['customer_is_guest'],
            'total' => $this->money->format((float)$row['base_grand_total'], $currency),
            'status' => (string)$this->orderConfig->getStatusLabel($row['status']),
            'state' => (string)$row['state'],
        ], $rows);
    }

    /**
     * Current month against its target (null without a target).
     *
     * @param string $currency
     * @return array|null
     */
    private function targetProgress(string $currency): ?array
    {
        $today = $this->today();
        $target = $this->targetRepository->getByPeriod($today->format('Y-m'));
        if (!$target) {
            return null;
        }
        [$from, $to] = $this->utcRange($today->format('Y-m-01'), $today->format('Y-m-d'));
        $totals = $this->analytics->getTotals($from, $to);
        $orders = max(0, $totals['orders'] - $totals['canceled']);
        $elapsed = (int)$today->format('j');
        $daysInMonth = (int)$today->format('t');
        $projected = $elapsed ? $totals['revenue'] / $elapsed * $daysInMonth : 0.0;
        $pct = static fn (float $value, float $goal) => $goal > 0 ? round($value / $goal * 100, 1) : 0.0;

        return [
            'month' => $today->format('F Y'),
            'revenue' => $this->money->format($totals['revenue'], $currency),
            'revenue_target' => $this->money->format($target->getRevenueTarget(), $currency),
            'revenue_pct' => $pct($totals['revenue'], $target->getRevenueTarget()),
            'orders' => $orders,
            'orders_target' => $target->getOrdersTarget(),
            'orders_pct' => $pct($orders, (float)$target->getOrdersTarget()),
            'projected' => $this->money->format($projected, $currency),
            'projected_pct' => $pct($projected, $target->getRevenueTarget()),
            'elapsed_pct' => round($elapsed / $daysInMonth * 100, 1),
            'days_left' => $daysInMonth - $elapsed,
            'note' => $target->getNote(),
        ];
    }

    /**
     * Plot box inside a chart of the given height.
     *
     * @param int $height
     * @return array{x: float, y: float, width: float, height: float}
     */
    private function plotBox(int $height): array
    {
        return [
            'x' => (float)self::PLOT_LEFT,
            'y' => (float)self::PLOT_TOP,
            'width' => (float)(self::CHART_WIDTH - self::PLOT_LEFT - self::PLOT_RIGHT),
            'height' => (float)($height - self::PLOT_TOP - self::PLOT_BOTTOM),
        ];
    }

    /**
     * Y positions and labels of the ticks.
     *
     * @param float[] $ticks
     * @param array $box
     * @param float $top
     * @param callable(float): string $format
     * @return array<int, array{y: float, label: string}>
     */
    private function tickRows(array $ticks, array $box, float $top, callable $format): array
    {
        return array_map(static fn (float $tick) => [
            'y' => round($box['y'] + $box['height'] - ($top > 0 ? $box['height'] * $tick / $top : 0), 2),
            'label' => $format($tick),
        ], $ticks);
    }

    /**
     * Evenly spaced x-axis labels.
     *
     * @param string[] $days
     * @param float[] $xs
     * @return array<int, array{x: float, label: string}>
     */
    private function xLabels(array $days, array $xs): array
    {
        $labels = [];
        foreach ($this->geometry->labelIndexes(count($days)) as $index) {
            $labels[] = ['x' => $xs[$index], 'label' => (new DateTimeImmutable($days[$index]))->format('j M')];
        }

        return $labels;
    }

    /**
     * "Fri, 14 Nov".
     *
     * @param string $day
     * @return string
     */
    private function dayLabel(string $day): string
    {
        return (new DateTimeImmutable($day))->format('D, j M');
    }
}
