<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use QuickCart\OrderTracking\Model\EventRecorder;
use QuickCart\OrderTracking\Model\Milestone;
use QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent;

/**
 * Gives orders placed before the module was installed an "Order placed" event (at the
 * original order time) and, when they have moved on, their current milestone.
 */
class BackfillTrackingEvents implements DataPatchInterface
{
    /**
     * @param ResourceConnection $resource
     * @param EventRecorder $recorder
     * @param Milestone $milestone
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EventRecorder $recorder,
        private readonly Milestone $milestone
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $connection = $this->resource->getConnection('sales');
        $orders = $connection->fetchAll(
            $connection->select()
                ->from(['o' => $this->resource->getTableName('sales_order', 'sales')], [
                    'entity_id', 'state', 'status', 'created_at', 'updated_at',
                ])
                ->joinLeft(
                    ['e' => $this->resource->getTableName(TrackingEvent::TABLE, 'sales')],
                    'e.order_id = o.entity_id',
                    []
                )
                ->where('e.event_id IS NULL')
        );
        foreach ($orders as $order) {
            $orderId = (int)$order['entity_id'];
            $this->recorder->record(
                $orderId,
                Milestone::PLACED,
                '',
                (string)__('We have received your order.'),
                null,
                (string)$order['created_at']
            );
            $current = $this->milestone->fromOrderState((string)$order['state'], (string)$order['status']);
            if ($current !== null && $current !== Milestone::PLACED) {
                $this->recorder->record($orderId, $current, '', null, null, (string)$order['updated_at']);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [AddOrderStatuses::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
