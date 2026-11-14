<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;
use QuickCart\CatalogEnhancement\Api\Data\LabelSearchResultsInterface;

/**
 * Product label rules CRUD.
 *
 * @api
 */
interface LabelRepositoryInterface
{
    /**
     * Validate and save a label.
     *
     * @param \QuickCart\CatalogEnhancement\Api\Data\LabelInterface $label
     * @return \QuickCart\CatalogEnhancement\Api\Data\LabelInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(LabelInterface $label): LabelInterface;

    /**
     * Load a label.
     *
     * @param int $labelId
     * @return \QuickCart\CatalogEnhancement\Api\Data\LabelInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $labelId): LabelInterface;

    /**
     * Search labels.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \QuickCart\CatalogEnhancement\Api\Data\LabelSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LabelSearchResultsInterface;

    /**
     * Delete a label.
     *
     * @param \QuickCart\CatalogEnhancement\Api\Data\LabelInterface $label
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(LabelInterface $label): bool;

    /**
     * Delete a label by ID.
     *
     * @param int $labelId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $labelId): bool;
}
