<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\ProductSource;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use QuickCart\CatalogEnhancement\Api\ProductSourceInterface;

/**
 * Products whose "Set Product as New" date range includes today (store timezone),
 * newest first - the same rule as Magento's native "Catalog New Products List".
 */
class NewArrivalsSource implements ProductSourceInterface
{
    /**
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string)__('New arrivals ("Set Product as New" dates)');
    }

    /**
     * @inheritdoc
     */
    public function apply(Collection $collection): void
    {
        $todayStart = $this->timezone->date()->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $todayEnd = $this->timezone->date()->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $collection->addAttributeToFilter(
            'news_from_date',
            ['or' => [['date' => true, 'to' => $todayEnd], ['is' => new Expression('null')]]],
            'left'
        )->addAttributeToFilter(
            'news_to_date',
            ['or' => [['date' => true, 'from' => $todayStart], ['is' => new Expression('null')]]],
            'left'
        )->addAttributeToFilter([
            ['attribute' => 'news_from_date', 'is' => new Expression('not null')],
            ['attribute' => 'news_to_date', 'is' => new Expression('not null')],
        ])->addAttributeToSort('news_from_date', Collection::SORT_ORDER_DESC);
    }
}
