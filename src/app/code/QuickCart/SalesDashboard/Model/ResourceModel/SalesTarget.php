<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use QuickCart\SalesDashboard\Api\Data\SalesTargetInterface;

/**
 * Sales target resource model.
 */
class SalesTarget extends AbstractDb
{
    public const TABLE = 'quickcart_sales_target';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, SalesTargetInterface::TARGET_ID);
    }
}
