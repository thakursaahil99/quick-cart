<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;
use QuickCart\CatalogEnhancement\Api\Data\LabelSearchResultsInterface;
use QuickCart\CatalogEnhancement\Api\Data\LabelSearchResultsInterfaceFactory;
use QuickCart\CatalogEnhancement\Api\LabelRepositoryInterface;
use QuickCart\CatalogEnhancement\Model\Label\Validator;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label as LabelResource;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label\CollectionFactory;

/**
 * Product label repository.
 */
class LabelRepository implements LabelRepositoryInterface
{
    /**
     * @param LabelResource $resource
     * @param LabelFactory $labelFactory
     * @param CollectionFactory $collectionFactory
     * @param LabelSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param Validator $validator
     */
    public function __construct(
        private readonly LabelResource $resource,
        private readonly LabelFactory $labelFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly LabelSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly Validator $validator
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(LabelInterface $label): LabelInterface
    {
        $this->validator->normalize($label);
        $errors = $this->validator->validate($label);
        if ($errors) {
            throw new CouldNotSaveException(__(implode(' ', $errors)));
        }
        try {
            /** @var Label $label */
            $this->resource->save($label);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the label: %1', $e->getMessage()), $e);
        }

        return $label;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $labelId): LabelInterface
    {
        $label = $this->labelFactory->create();
        $this->resource->load($label, $labelId);
        if (!$label->getId()) {
            throw new NoSuchEntityException(__('The product label with ID "%1" does not exist.', $labelId));
        }

        return $label;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LabelSearchResultsInterface
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
    public function delete(LabelInterface $label): bool
    {
        try {
            /** @var Label $label */
            $this->resource->delete($label);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the label: %1', $e->getMessage()), $e);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $labelId): bool
    {
        return $this->delete($this->getById($labelId));
    }
}
