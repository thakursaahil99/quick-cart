<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model;

use Magento\Framework\Model\AbstractModel;
use QuickCart\SalesDashboard\Api\Data\SalesTargetInterface;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesTarget as SalesTargetResource;

/**
 * Monthly sales target.
 */
class SalesTarget extends AbstractModel implements SalesTargetInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'quickcart_sales_target';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(SalesTargetResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getTargetId(): ?int
    {
        $id = $this->getData(self::TARGET_ID);

        return $id === null ? null : (int)$id;
    }

    /**
     * @inheritdoc
     */
    public function getPeriod(): string
    {
        return (string)$this->getData(self::PERIOD);
    }

    /**
     * @inheritdoc
     */
    public function setPeriod(string $period): SalesTargetInterface
    {
        return $this->setData(self::PERIOD, $period);
    }

    /**
     * @inheritdoc
     */
    public function getRevenueTarget(): float
    {
        return (float)$this->getData(self::REVENUE_TARGET);
    }

    /**
     * @inheritdoc
     */
    public function setRevenueTarget(float $amount): SalesTargetInterface
    {
        return $this->setData(self::REVENUE_TARGET, $amount);
    }

    /**
     * @inheritdoc
     */
    public function getOrdersTarget(): int
    {
        return (int)$this->getData(self::ORDERS_TARGET);
    }

    /**
     * @inheritdoc
     */
    public function setOrdersTarget(int $orders): SalesTargetInterface
    {
        return $this->setData(self::ORDERS_TARGET, $orders);
    }

    /**
     * @inheritdoc
     */
    public function getNote(): ?string
    {
        $note = $this->getData(self::NOTE);

        return $note === null || $note === '' ? null : (string)$note;
    }

    /**
     * @inheritdoc
     */
    public function setNote(?string $note): SalesTargetInterface
    {
        return $this->setData(self::NOTE, $note);
    }
}
