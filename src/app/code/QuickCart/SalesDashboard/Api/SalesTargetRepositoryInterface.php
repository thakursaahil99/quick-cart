<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Api;

use QuickCart\SalesDashboard\Api\Data\SalesTargetInterface;

/**
 * Monthly sales targets.
 *
 * @api
 */
interface SalesTargetRepositoryInterface
{
    /**
     * Validate and save a target (one per month).
     *
     * @param \QuickCart\SalesDashboard\Api\Data\SalesTargetInterface $target
     * @return \QuickCart\SalesDashboard\Api\Data\SalesTargetInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(SalesTargetInterface $target): SalesTargetInterface;

    /**
     * Load a target.
     *
     * @param int $targetId
     * @return \QuickCart\SalesDashboard\Api\Data\SalesTargetInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $targetId): SalesTargetInterface;

    /**
     * Target of a month (YYYY-MM), or null.
     *
     * @param string $period
     * @return \QuickCart\SalesDashboard\Api\Data\SalesTargetInterface|null
     */
    public function getByPeriod(string $period): ?SalesTargetInterface;

    /**
     * Delete a target.
     *
     * @param \QuickCart\SalesDashboard\Api\Data\SalesTargetInterface $target
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(SalesTargetInterface $target): bool;
}
