<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\SalesDashboard\Api\Data\SalesTargetInterface;
use QuickCart\SalesDashboard\Api\SalesTargetRepositoryInterface;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesTarget as SalesTargetResource;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesTarget\CollectionFactory;

/**
 * Sales target repository.
 */
class SalesTargetRepository implements SalesTargetRepositoryInterface
{
    public const NOTE_MAX_LENGTH = 255;
    public const MAX_REVENUE = 1e13;

    /**
     * @param SalesTargetResource $resource
     * @param SalesTargetFactory $targetFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly SalesTargetResource $resource,
        private readonly SalesTargetFactory $targetFactory,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(SalesTargetInterface $target): SalesTargetInterface
    {
        $target->setPeriod(trim($target->getPeriod()));
        $target->setNote($target->getNote() === null ? null : trim($target->getNote()));
        $errors = $this->validate($target);
        if ($errors) {
            throw new CouldNotSaveException(__(implode(' ', $errors)));
        }
        try {
            /** @var SalesTarget $target */
            $this->resource->save($target);
        } catch (AlreadyExistsException $e) {
            throw new CouldNotSaveException(
                __('A target for %1 already exists. Edit that target instead.', $target->getPeriod()),
                $e
            );
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the target: %1', $e->getMessage()), $e);
        }

        return $target;
    }

    /**
     * Validation errors (empty when valid).
     *
     * @param SalesTargetInterface $target
     * @return string[]
     */
    public function validate(SalesTargetInterface $target): array
    {
        $errors = [];
        $period = $target->getPeriod();
        if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $period, $match) || (int)$match[1] < 2000) {
            $errors[] = (string)__('Month must be in YYYY-MM format, e.g. 2026-11.');
        }
        if ($target->getRevenueTarget() <= 0 || $target->getRevenueTarget() > self::MAX_REVENUE) {
            $errors[] = (string)__('Revenue target must be a positive amount.');
        }
        if ($target->getOrdersTarget() < 0) {
            $errors[] = (string)__('Orders target cannot be negative.');
        }
        if (mb_strlen((string)$target->getNote()) > self::NOTE_MAX_LENGTH) {
            $errors[] = (string)__('The note is limited to %1 characters.', self::NOTE_MAX_LENGTH);
        }

        return $errors;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $targetId): SalesTargetInterface
    {
        $target = $this->targetFactory->create();
        $this->resource->load($target, $targetId);
        if (!$target->getId()) {
            throw new NoSuchEntityException(__('The sales target with ID "%1" does not exist.', $targetId));
        }

        return $target;
    }

    /**
     * @inheritdoc
     */
    public function getByPeriod(string $period): ?SalesTargetInterface
    {
        /** @var SalesTarget $target */
        $target = $this->collectionFactory->create()
            ->addFieldToFilter(SalesTargetInterface::PERIOD, $period)
            ->setPageSize(1)
            ->getFirstItem();

        return $target->getId() ? $target : null;
    }

    /**
     * @inheritdoc
     */
    public function delete(SalesTargetInterface $target): bool
    {
        try {
            /** @var SalesTarget $target */
            $this->resource->delete($target);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the target: %1', $e->getMessage()), $e);
        }

        return true;
    }
}
