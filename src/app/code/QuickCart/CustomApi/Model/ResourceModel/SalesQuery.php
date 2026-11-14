<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Sales\Model\Order;

/**
 * Aggregate queries on sales_order for the stats and customer insight endpoints.
 *
 * Revenue = base grand total minus base refunds, cancelled orders excluded.
 */
class SalesQuery
{
    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Orders and revenue per status between two UTC timestamps.
     *
     * @param string $fromUtc
     * @param string $toUtc
     * @return array<int, array{state: string, status: string, orders: int, revenue: float}>
     */
    public function getStatusTotals(string $fromUtc, string $toUtc): array
    {
        $connection = $this->resource->getConnection('sales');
        $select = $connection->select()
            ->from(
                $this->resource->getTableName('sales_order', 'sales'),
                [
                    'state',
                    'status',
                    'orders' => new Expression('COUNT(*)'),
                    'revenue' => new Expression(
                        'SUM(base_grand_total - IFNULL(base_total_refunded, 0))'
                    ),
                ]
            )
            ->where('created_at >= ?', $fromUtc)
            ->where('created_at <= ?', $toUtc)
            ->group(['state', 'status'])
            ->order('orders DESC');

        return array_map(static fn (array $row) => [
            'state' => (string)$row['state'],
            'status' => (string)$row['status'],
            'orders' => (int)$row['orders'],
            'revenue' => (float)$row['revenue'],
        ], $connection->fetchAll($select));
    }

    /**
     * Non-cancelled order count, net revenue and last order time of a customer.
     *
     * @param int $customerId
     * @return array{orders: int, revenue: float, last_order_at: string|null}
     */
    public function getCustomerTotals(int $customerId): array
    {
        $connection = $this->resource->getConnection('sales');
        $row = $connection->fetchRow(
            $connection->select()
                ->from(
                    $this->resource->getTableName('sales_order', 'sales'),
                    [
                        'orders' => new Expression('COUNT(*)'),
                        'revenue' => new Expression(
                            'IFNULL(SUM(base_grand_total - IFNULL(base_total_refunded, 0)), 0)'
                        ),
                        'last_order_at' => new Expression('MAX(created_at)'),
                    ]
                )
                ->where('customer_id = ?', $customerId)
                ->where('state <> ?', Order::STATE_CANCELED)
        ) ?: [];

        return [
            'orders' => (int)($row['orders'] ?? 0),
            'revenue' => (float)($row['revenue'] ?? 0),
            'last_order_at' => $row['last_order_at'] ?? null,
        ];
    }
}
