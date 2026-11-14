<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;

/**
 * Recommendation CRUD.
 *
 * @api
 */
interface RecommendationRepositoryInterface
{
    /**
     * Validate and save a recommendation.
     *
     * @param \QuickCart\ProductRecommendation\Api\Data\RecommendationInterface $recommendation
     * @return \QuickCart\ProductRecommendation\Api\Data\RecommendationInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(RecommendationInterface $recommendation): RecommendationInterface;

    /**
     * Load a recommendation.
     *
     * @param int $recommendationId
     * @return \QuickCart\ProductRecommendation\Api\Data\RecommendationInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $recommendationId): RecommendationInterface;

    /**
     * Search recommendations.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete a recommendation.
     *
     * @param \QuickCart\ProductRecommendation\Api\Data\RecommendationInterface $recommendation
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(RecommendationInterface $recommendation): bool;

    /**
     * Delete a recommendation by ID.
     *
     * @param int $recommendationId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $recommendationId): bool;
}
