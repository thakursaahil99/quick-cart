<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Admin grid collection: events with their order number, customer and current status.
 */
class Collection extends SearchResult
{
    private const MAIN_COLUMNS = [
        'event_id', 'order_id', 'milestone', 'title', 'comment', 'location', 'is_visible_on_front',
        'source', 'reference', 'created_by', 'created_at',
    ];

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['so' => $this->getTable('sales_order')],
            'so.entity_id = main_table.order_id',
            [
                'increment_id' => 'so.increment_id',
                'order_status' => 'so.status',
                'customer_email' => 'so.customer_email',
                'customer_name' => new \Magento\Framework\DB\Sql\Expression(
                    "TRIM(CONCAT_WS(' ', so.customer_firstname, so.customer_lastname))"
                ),
            ]
        );
        foreach (self::MAIN_COLUMNS as $column) {
            $this->addFilterToMap($column, 'main_table.' . $column);
        }
        $this->addFilterToMap('increment_id', 'so.increment_id')
            ->addFilterToMap('order_status', 'so.status')
            ->addFilterToMap('customer_email', 'so.customer_email');

        return $this;
    }
}
