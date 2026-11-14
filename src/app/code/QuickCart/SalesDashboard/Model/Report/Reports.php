<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\Report;

use QuickCart\SalesDashboard\Model\ResourceModel\SalesAnalytics;

/**
 * Date-wise sales and best-selling product reports (screen and CSV).
 */
class Reports
{
    public const PRODUCT_LIMIT = 100;

    /**
     * @param Dashboard $dashboard
     * @param SalesAnalytics $analytics
     */
    public function __construct(
        private readonly Dashboard $dashboard,
        private readonly SalesAnalytics $analytics
    ) {
    }

    /**
     * One row per day (newest first) plus totals.
     *
     * @param array $period
     * @return array{rows: array, totals: array, currency: string}
     */
    public function daily(array $period): array
    {
        [$from, $to] = $this->dashboard->utcRange($period['from'], $period['to']);
        $rows = array_reverse(array_values($this->dashboard->dailySeries($period['from'], $period['to'], $from, $to)));
        $totals = ['orders' => 0, 'canceled' => 0, 'revenue' => 0.0, 'items' => 0.0, 'discount' => 0.0, 'shipping' => 0.0];
        foreach ($rows as $index => $row) {
            foreach (array_keys($totals) as $key) {
                $totals[$key] += $row[$key];
            }
            $paid = $row['orders'] - $row['canceled'];
            $rows[$index]['aov'] = $paid > 0 ? $row['revenue'] / $paid : 0.0;
        }
        $paid = $totals['orders'] - $totals['canceled'];
        $totals['aov'] = $paid > 0 ? $totals['revenue'] / $paid : 0.0;

        return ['rows' => $rows, 'totals' => $totals, 'currency' => $this->dashboard->getCurrency()];
    }

    /**
     * Best-selling products by net revenue.
     *
     * @param array $period
     * @return array{rows: array, currency: string}
     */
    public function products(array $period): array
    {
        [$from, $to] = $this->dashboard->utcRange($period['from'], $period['to']);
        $rows = $this->analytics->getTopProducts($from, $to, self::PRODUCT_LIMIT);
        $total = array_sum(array_column($rows, 'revenue'));
        foreach ($rows as $index => $row) {
            $rows[$index]['rank'] = $index + 1;
            $rows[$index]['share'] = $total > 0 ? round($row['revenue'] / $total * 100, 1) : 0.0;
        }

        return ['rows' => $rows, 'currency' => $this->dashboard->getCurrency()];
    }

    /**
     * CSV text; cells that a spreadsheet would run as a formula are prefixed with a quote.
     *
     * @param string[] $header
     * @param array<int, array<int, string|int|float>> $rows
     * @return string
     */
    public function toCsv(array $header, array $rows): string
    {
        $lines = [];
        foreach (array_merge([$header], $rows) as $row) {
            $cells = [];
            foreach ($row as $cell) {
                $cell = (string)$cell;
                if ($cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true) && !is_numeric($cell)) {
                    $cell = "'" . $cell;
                }
                $cells[] = '"' . str_replace('"', '""', $cell) . '"';
            }
            $lines[] = implode(',', $cells);
        }

        return implode("\r\n", $lines) . "\r\n";
    }
}
