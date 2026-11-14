<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use QuickCart\OrderTracking\Api\Data\TrackingEventInterface;

/**
 * Tracking event resource model.
 */
class TrackingEvent extends AbstractDb
{
    public const TABLE = 'quickcart_order_tracking_event';

    /**
     * @var string
     */
    protected $connectionName = 'sales';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, TrackingEventInterface::EVENT_ID);
    }

    /**
     * Milestone of the order's most recent event, or null.
     *
     * @param int $orderId
     * @return string|null
     */
    public function getLastMilestone(int $orderId): ?string
    {
        $connection = $this->getConnection();
        $milestone = $connection->fetchOne(
            $connection->select()
                ->from($this->getMainTable(), ['milestone'])
                ->where('order_id = ?', $orderId)
                ->order(['created_at DESC', 'event_id DESC'])
                ->limit(1)
        );

        return $milestone === false ? null : (string)$milestone;
    }

    /**
     * Milestones of all events of an order, oldest first.
     *
     * @param int $orderId
     * @return string[]
     */
    public function getMilestones(int $orderId): array
    {
        $connection = $this->getConnection();

        return $connection->fetchCol(
            $connection->select()
                ->from($this->getMainTable(), ['milestone'])
                ->where('order_id = ?', $orderId)
                ->order(['created_at ASC', 'event_id ASC'])
        );
    }

    /**
     * Whether an event with this reference already exists for the order.
     *
     * @param int $orderId
     * @param string $reference
     * @return bool
     */
    public function hasReference(int $orderId, string $reference): bool
    {
        $connection = $this->getConnection();

        return (bool)$connection->fetchOne(
            $connection->select()
                ->from($this->getMainTable(), ['event_id'])
                ->where('order_id = ?', $orderId)
                ->where('reference = ?', $reference)
                ->limit(1)
        );
    }
}
