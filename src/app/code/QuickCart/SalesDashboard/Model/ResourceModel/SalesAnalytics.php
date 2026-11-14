<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Sales\Model\Order;

/**
 * Live sales aggregates for the dashboard and reports.
 *
 * Every query is bounded by sales_order.created_at (indexed) and uses one GROUP BY, so a
 * dashboard render costs a handful of indexed queries. Revenue = base grand total minus
 * base refunds of non-cancelled orders, in the base currency.
 */
class SalesAnalytics
{
    private const NET = 'IF(o.state <> \'' . Order::STATE_CANCELED . '\', '
        . 'o.base_grand_total - IFNULL(o.base_total_refunded, 0), 0)';
    private const LIVE = 'o.state <> \'' . Order::STATE_CANCELED . '\'';

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Headline totals between two UTC timestamps.
     *
     * @param string $fromUtc
     * @param string $toUtc
     * @return array{orders: int, canceled: int, revenue: float, items: float, discount: float, customers: int}
     */
    public function getTotals(string $fromUtc, string $toUtc): array
    {
        $connection = $this->connection();
        $row = $connection->fetchRow($this->ordersBetween($fromUtc, $toUtc)->columns([
            'orders' => new Expression('COUNT(*)'),
            'canceled' => new Expression('SUM(NOT (' . self::LIVE . '))'),
            'revenue' => new Expression('SUM(' . self::NET . ')'),
            'items' => new Expression('SUM(IF(' . self::LIVE . ', o.total_qty_ordered, 0))'),
            'discount' => new Expression('SUM(IF(' . self::LIVE . ', ABS(IFNULL(o.base_discount_amount, 0)), 0))'),
            'customers' => new Expression('COUNT(DISTINCT IF(' . self::LIVE . ', o.customer_email, NULL))'),
        ])) ?: [];

        return [
            'orders' => (int)($row['orders'] ?? 0),
            'canceled' => (int)($row['canceled'] ?? 0),
            'revenue' => (float)($row['revenue'] ?? 0),
            'items' => (float)($row['items'] ?? 0),
            'discount' => (float)($row['discount'] ?? 0),
            'customers' => (int)($row['customers'] ?? 0),
        ];
    }

    /**
     * Per-day figures keyed by store-local day (Y-m-d).
     *
     * @param string $fromUtc
     * @param string $toUtc
     * @param string $utcOffset Store timezone offset, e.g. +05:30
     * @return array<string, array{orders: int, canceled: int, revenue: float, items: float, discount: float, shipping: float}>
     */
    public function getDaily(string $fromUtc, string $toUtc, string $utcOffset): array
    {
        $connection = $this->connection();
        $day = new Expression(sprintf(
            "DATE(CONVERT_TZ(o.created_at, '+00:00', %s))",
            $connection->quote($this->sanitizeOffset($utcOffset))
        ));
        $select = $this->ordersBetween($fromUtc, $toUtc)
            ->columns([
                'day' => $day,
                'orders' => new Expression('COUNT(*)'),
                'canceled' => new Expression('SUM(NOT (' . self::LIVE . '))'),
                'revenue' => new Expression('SUM(' . self::NET . ')'),
                'items' => new Expression('SUM(IF(' . self::LIVE . ', o.total_qty_ordered, 0))'),
                'discount' => new Expression(
                    'SUM(IF(' . self::LIVE . ', ABS(IFNULL(o.base_discount_amount, 0)), 0))'
                ),
                'shipping' => new Expression('SUM(IF(' . self::LIVE . ', IFNULL(o.base_shipping_amount, 0), 0))'),
            ])
            ->group($day);

        $days = [];
        foreach ($connection->fetchAll($select) as $row) {
            $days[(string)$row['day']] = [
                'orders' => (int)$row['orders'],
                'canceled' => (int)$row['canceled'],
                'revenue' => (float)$row['revenue'],
                'items' => (float)$row['items'],
                'discount' => (float)$row['discount'],
                'shipping' => (float)$row['shipping'],
            ];
        }

        return $days;
    }

    /**
     * Orders and net revenue per order state.
     *
     * @param string $fromUtc
     * @param string $toUtc
     * @return array<string, array{orders: int, revenue: float}>
     */
    public function getStates(string $fromUtc, string $toUtc): array
    {
        $select = $this->ordersBetween($fromUtc, $toUtc)
            ->columns([
                'state' => 'o.state',
                'orders' => new Expression('COUNT(*)'),
                'revenue' => new Expression('SUM(o.base_grand_total - IFNULL(o.base_total_refunded, 0))'),
            ])
            ->group('o.state');

        $states = [];
        foreach ($this->connection()->fetchAll($select) as $row) {
            $states[(string)$row['state']] = ['orders' => (int)$row['orders'], 'revenue' => (float)$row['revenue']];
        }

        return $states;
    }

    /**
     * Best-selling products by net revenue (configurable variants roll up to the parent).
     *
     * @param string $fromUtc
     * @param string $toUtc
     * @param int $limit
     * @return array<int, array{product_id: int, sku: string, name: string, qty: float, revenue: float, orders: int}>
     */
    public function getTopProducts(string $fromUtc, string $toUtc, int $limit): array
    {
        $connection = $this->connection();
        $select = $connection->select()
            ->from(['i' => $this->table('sales_order_item')], [])
            ->join(['o' => $this->table('sales_order')], 'o.entity_id = i.order_id', [])
            ->joinLeft(['p' => $this->table('catalog_product_entity')], 'p.entity_id = i.product_id', [])
            ->columns([
                'product_id' => 'i.product_id',
                'sku' => new Expression('COALESCE(p.sku, MAX(i.sku))'),
                'name' => new Expression('MAX(i.name)'),
                'qty' => new Expression('SUM(i.qty_ordered)'),
                'revenue' => new Expression('SUM(i.base_row_total - IFNULL(i.base_discount_amount, 0))'),
                'orders' => new Expression('COUNT(DISTINCT i.order_id)'),
            ])
            ->where('i.parent_item_id IS NULL')
            ->where(self::LIVE)
            ->where('o.created_at >= ?', $fromUtc)
            ->where('o.created_at <= ?', $toUtc)
            ->group(['i.product_id', 'p.sku'])
            ->order('revenue DESC')
            ->limit(max(1, $limit));

        return array_map(static fn (array $row) => [
            'product_id' => (int)$row['product_id'],
            'sku' => (string)$row['sku'],
            'name' => (string)$row['name'],
            'qty' => (float)$row['qty'],
            'revenue' => (float)$row['revenue'],
            'orders' => (int)$row['orders'],
        ], $connection->fetchAll($select));
    }

    /**
     * Non-cancelled orders and revenue per payment method code.
     *
     * @param string $fromUtc
     * @param string $toUtc
     * @return array<string, array{orders: int, revenue: float}>
     */
    public function getPaymentMethods(string $fromUtc, string $toUtc): array
    {
        $select = $this->ordersBetween($fromUtc, $toUtc)
            ->join(['pay' => $this->table('sales_order_payment')], 'pay.parent_id = o.entity_id', [])
            ->columns([
                'method' => 'pay.method',
                'orders' => new Expression('COUNT(*)'),
                'revenue' => new Expression('SUM(' . self::NET . ')'),
            ])
            ->where(self::LIVE)
            ->group('pay.method')
            ->order('orders DESC');

        $methods = [];
        foreach ($this->connection()->fetchAll($select) as $row) {
            $methods[(string)$row['method']] = ['orders' => (int)$row['orders'], 'revenue' => (float)$row['revenue']];
        }

        return $methods;
    }

    /**
     * Latest orders.
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getRecentOrders(int $limit): array
    {
        $connection = $this->connection();

        return $connection->fetchAll(
            $connection->select()
                ->from(['o' => $this->table('sales_order')], [
                    'entity_id', 'increment_id', 'created_at', 'state', 'status', 'base_grand_total',
                    'customer_firstname', 'customer_lastname', 'customer_is_guest',
                ])
                ->order('o.created_at DESC')
                ->limit(max(1, $limit))
        );
    }

    /**
     * Customer accounts created between two UTC timestamps.
     *
     * @param string $fromUtc
     * @param string $toUtc
     * @return int
     */
    public function countNewCustomers(string $fromUtc, string $toUtc): int
    {
        $connection = $this->resource->getConnection();

        return (int)$connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('customer_entity'), [new Expression('COUNT(*)')])
                ->where('created_at >= ?', $fromUtc)
                ->where('created_at <= ?', $toUtc)
        );
    }

    /**
     * Base select of orders in a UTC range (alias "o", no columns).
     *
     * @param string $fromUtc
     * @param string $toUtc
     * @return Select
     */
    private function ordersBetween(string $fromUtc, string $toUtc): Select
    {
        return $this->connection()->select()
            ->from(['o' => $this->table('sales_order')], [])
            ->where('o.created_at >= ?', $fromUtc)
            ->where('o.created_at <= ?', $toUtc);
    }

    /**
     * "+05:30"-style offset only (anything else becomes UTC).
     *
     * @param string $offset
     * @return string
     */
    private function sanitizeOffset(string $offset): string
    {
        return preg_match('/^[+-](0\d|1[0-4]):[0-5]\d$/', $offset) ? $offset : '+00:00';
    }

    /**
     * Sales connection.
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function connection()
    {
        return $this->resource->getConnection('sales');
    }

    /**
     * Table name on the sales connection.
     *
     * @param string $name
     * @return string
     */
    private function table(string $name): string
    {
        return $this->resource->getTableName($name, 'sales');
    }
}
