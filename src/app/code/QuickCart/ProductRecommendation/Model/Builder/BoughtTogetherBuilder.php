<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model\Builder;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order;

/**
 * Mines "frequently bought together" pairs from real order history.
 *
 * Two top-level order items (configurable/bundle parents, not their child rows) in the
 * same non-cancelled order form a co-purchase. The number of distinct orders a pair
 * shares is its score; each product keeps its best N partners.
 */
class BoughtTogetherBuilder
{
    /**
     * @param ResourceConnection $resource
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * Compute pairs from orders placed in the last $lookbackDays days.
     *
     * @param int $lookbackDays
     * @param int $minOrders
     * @param int $maxPerProduct
     * @return array<int, array{product_id: int, recommended_product_id: int, score: float}>
     */
    public function build(int $lookbackDays, int $minOrders, int $maxPerProduct): array
    {
        $connection = $this->resource->getConnection('sales');
        $items = $this->resource->getTableName('sales_order_item', 'sales');
        $products = $this->resource->getTableName('catalog_product_entity');
        // sales_order.created_at is stored in UTC.
        $since = $this->dateTime->gmtDate('Y-m-d H:i:s', strtotime(sprintf('-%d days', $lookbackDays)));

        $orders = new Expression('COUNT(DISTINCT a.order_id)');
        $select = $connection->select()
            ->from(
                ['a' => $items],
                ['product_id' => 'a.product_id', 'recommended_product_id' => 'b.product_id', 'orders' => $orders]
            )
            ->join(
                ['b' => $items],
                'b.order_id = a.order_id AND b.product_id <> a.product_id AND b.parent_item_id IS NULL',
                []
            )
            ->join(['o' => $this->resource->getTableName('sales_order', 'sales')], 'o.entity_id = a.order_id', [])
            ->join(['pa' => $products], 'pa.entity_id = a.product_id', [])
            ->join(['pb' => $products], 'pb.entity_id = b.product_id', [])
            ->where('a.parent_item_id IS NULL')
            ->where('o.state <> ?', Order::STATE_CANCELED)
            ->where('o.created_at >= ?', $since)
            ->group(['a.product_id', 'b.product_id'])
            ->having('COUNT(DISTINCT a.order_id) >= ?', max(1, $minOrders));

        return $this->rank($connection->fetchAll($select), $maxPerProduct);
    }

    /**
     * Keep the best $max partners per product (most shared orders, then lowest ID for stability).
     *
     * @param array<int, array{product_id: int|string, recommended_product_id: int|string, orders: int|string}> $pairs
     * @param int $max
     * @return array<int, array{product_id: int, recommended_product_id: int, score: float}>
     */
    public function rank(array $pairs, int $max): array
    {
        $byProduct = [];
        foreach ($pairs as $pair) {
            $productId = (int)$pair['product_id'];
            $partnerId = (int)$pair['recommended_product_id'];
            if ($productId === $partnerId || $productId < 1 || $partnerId < 1) {
                continue;
            }
            $byProduct[$productId][$partnerId] = (int)$pair['orders'];
        }

        $rows = [];
        foreach ($byProduct as $productId => $partners) {
            uksort($partners, static function (int $left, int $right) use ($partners): int {
                return [$partners[$right], $left] <=> [$partners[$left], $right];
            });
            foreach (array_slice($partners, 0, max(1, $max), true) as $partnerId => $orders) {
                $rows[] = [
                    'product_id' => $productId,
                    'recommended_product_id' => $partnerId,
                    'score' => (float)$orders,
                ];
            }
        }

        return $rows;
    }
}
