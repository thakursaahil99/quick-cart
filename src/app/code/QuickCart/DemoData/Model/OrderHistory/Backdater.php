<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Model\OrderHistory;

use DateTimeImmutable;
use Magento\Framework\App\ResourceConnection;

/**
 * Moves a freshly generated order (and its items, invoices, shipments, status history,
 * grid rows and tracking timeline) back to its historical date, so reports and the
 * dashboard see a realistic order history. Demo-data tooling only.
 */
class Backdater
{
    public const DEMO_MARKER = 'qc-demo';
    private const FORMAT = 'Y-m-d H:i:s';

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Rewrite the timestamps of an order placed at $placedAt (UTC).
     *
     * @param int $orderId
     * @param DateTimeImmutable $placedAt
     * @param int $lastStepHours Hours from placing to the order's last change
     * @return void
     */
    public function backdate(int $orderId, DateTimeImmutable $placedAt, int $lastStepHours): void
    {
        $connection = $this->resource->getConnection('sales');
        $placed = $placedAt->format(self::FORMAT);
        $updated = $placedAt->modify('+' . $lastStepHours . ' hours')->format(self::FORMAT);
        $shipped = $placedAt->modify('+' . Scenario::MILESTONE_HOURS['shipped'] . ' hours')->format(self::FORMAT);
        $delivered = $placedAt->modify('+' . Scenario::MILESTONE_HOURS['delivered'] . ' hours')->format(self::FORMAT);

        $connection->update(
            $this->table('sales_order'),
            ['created_at' => $placed, 'updated_at' => $updated, 'ext_order_id' => self::DEMO_MARKER],
            ['entity_id = ?' => $orderId]
        );
        $connection->update(
            $this->table('sales_order_grid'),
            ['created_at' => $placed, 'updated_at' => $updated],
            ['entity_id = ?' => $orderId]
        );
        $connection->update(
            $this->table('sales_order_item'),
            ['created_at' => $placed, 'updated_at' => $updated],
            ['order_id = ?' => $orderId]
        );
        $connection->update(
            $this->table('sales_order_status_history'),
            ['created_at' => $updated],
            ['parent_id = ?' => $orderId]
        );
        $invoiced = ['created_at' => $delivered, 'updated_at' => $delivered];
        $connection->update($this->table('sales_invoice'), $invoiced, ['order_id = ?' => $orderId]);
        $connection->update(
            $this->table('sales_invoice_grid'),
            $invoiced + ['order_created_at' => $placed],
            ['order_id = ?' => $orderId]
        );
        $shippedAt = ['created_at' => $shipped, 'updated_at' => $shipped];
        $connection->update($this->table('sales_shipment'), $shippedAt, ['order_id = ?' => $orderId]);
        $connection->update($this->table('sales_shipment_track'), $shippedAt, ['order_id = ?' => $orderId]);
        $connection->update(
            $this->table('sales_shipment_grid'),
            $shippedAt + ['order_created_at' => $placed],
            ['order_id = ?' => $orderId]
        );
        $this->backdateTimeline($orderId, $placedAt);
    }

    /**
     * Give each tracking event the time of its milestone (several events of the same
     * milestone, e.g. shipment + tracking number, are a few minutes apart).
     *
     * @param int $orderId
     * @param DateTimeImmutable $placedAt
     * @return void
     */
    private function backdateTimeline(int $orderId, DateTimeImmutable $placedAt): void
    {
        $connection = $this->resource->getConnection('sales');
        $table = $this->table('quickcart_order_tracking_event');
        $events = $connection->fetchPairs(
            $connection->select()
                ->from($table, ['event_id', 'milestone'])
                ->where('order_id = ?', $orderId)
                ->order('event_id ASC')
        );
        $seen = [];
        foreach ($events as $eventId => $milestone) {
            $hours = Scenario::MILESTONE_HOURS[$milestone] ?? 1;
            $minutes = 7 * ($seen[$milestone] ?? 0);
            $seen[$milestone] = ($seen[$milestone] ?? 0) + 1;
            $connection->update(
                $table,
                ['created_at' => $placedAt->modify(sprintf('+%d hours +%d minutes', $hours, $minutes))->format(self::FORMAT)],
                ['event_id = ?' => (int)$eventId]
            );
        }
    }

    /**
     * Customer accounts are created a few days before their first order.
     *
     * @param int $customerId
     * @param DateTimeImmutable $createdAt
     * @return void
     */
    public function backdateCustomer(int $customerId, DateTimeImmutable $createdAt): void
    {
        $connection = $this->resource->getConnection();
        $values = ['created_at' => $createdAt->format(self::FORMAT), 'updated_at' => $createdAt->format(self::FORMAT)];
        $connection->update($this->table('customer_entity'), $values, ['entity_id = ?' => $customerId]);
        $connection->update(
            $this->table('customer_grid_flat'),
            ['created_at' => $values['created_at']],
            ['entity_id = ?' => $customerId]
        );
    }

    /**
     * Number of orders already generated.
     *
     * @return int
     */
    public function countGenerated(): int
    {
        $connection = $this->resource->getConnection('sales');

        return (int)$connection->fetchOne(
            $connection->select()
                ->from($this->table('sales_order'), ['COUNT(*)'])
                ->where('ext_order_id = ?', self::DEMO_MARKER)
        );
    }

    /**
     * Table name.
     *
     * @param string $name
     * @return string
     */
    private function table(string $name): string
    {
        return $this->resource->getTableName($name);
    }
}
