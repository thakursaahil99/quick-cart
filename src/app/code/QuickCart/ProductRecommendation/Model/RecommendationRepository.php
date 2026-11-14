<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;
use QuickCart\ProductRecommendation\Api\RecommendationRepositoryInterface;
use QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation as RecommendationResource;
use QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation\CollectionFactory;
use QuickCart\ProductRecommendation\Model\Source\Type;

/**
 * Recommendation repository.
 */
class RecommendationRepository implements RecommendationRepositoryInterface
{
    private const SOURCES = [
        RecommendationInterface::SOURCE_MANUAL,
        RecommendationInterface::SOURCE_ORDERS,
        RecommendationInterface::SOURCE_CATALOG,
    ];

    /**
     * @param RecommendationResource $resource
     * @param RecommendationFactory $recommendationFactory
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param Type $types
     */
    public function __construct(
        private readonly RecommendationResource $resource,
        private readonly RecommendationFactory $recommendationFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly Type $types
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(RecommendationInterface $recommendation): RecommendationInterface
    {
        if ($recommendation->getProductId() < 1 || $recommendation->getRecommendedProductId() < 1) {
            throw new CouldNotSaveException(__('Both products are required.'));
        }
        if ($recommendation->getProductId() === $recommendation->getRecommendedProductId()) {
            throw new CouldNotSaveException(__('A product cannot be recommended for itself.'));
        }
        if (!array_key_exists($recommendation->getType(), $this->types->getOptions())) {
            throw new CouldNotSaveException(__('Unknown recommendation type "%1".', $recommendation->getType()));
        }
        if (!in_array($recommendation->getSource(), self::SOURCES, true)) {
            throw new CouldNotSaveException(__('Unknown recommendation source "%1".', $recommendation->getSource()));
        }
        try {
            /** @var Recommendation $recommendation */
            $this->resource->save($recommendation);
        } catch (AlreadyExistsException $e) {
            throw new CouldNotSaveException(
                __('This recommendation already exists. Edit the existing row instead.'),
                $e
            );
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the recommendation: %1', $e->getMessage()), $e);
        }

        return $recommendation;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $recommendationId): RecommendationInterface
    {
        $recommendation = $this->recommendationFactory->create();
        $this->resource->load($recommendation, $recommendationId);
        if (!$recommendation->getId()) {
            throw new NoSuchEntityException(
                __('The recommendation with ID "%1" does not exist.', $recommendationId)
            );
        }

        return $recommendation;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(RecommendationInterface $recommendation): bool
    {
        try {
            /** @var Recommendation $recommendation */
            $this->resource->delete($recommendation);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the recommendation: %1', $e->getMessage()), $e);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $recommendationId): bool
    {
        return $this->delete($this->getById($recommendationId));
    }
}
