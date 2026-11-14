<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\ResourceModel\SalesTarget;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use QuickCart\SalesDashboard\Api\Data\SalesTargetInterface;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesTarget as SalesTargetResource;
use QuickCart\SalesDashboard\Model\SalesTarget;

/**
 * Sales target collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = SalesTargetInterface::TARGET_ID;

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(SalesTarget::class, SalesTargetResource::class);
    }
}
