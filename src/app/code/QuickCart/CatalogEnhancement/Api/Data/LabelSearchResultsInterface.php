<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Product label search results.
 *
 * @api
 */
interface LabelSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Labels.
     *
     * @return \QuickCart\CatalogEnhancement\Api\Data\LabelInterface[]
     */
    public function getItems();

    /**
     * Set labels.
     *
     * @param \QuickCart\CatalogEnhancement\Api\Data\LabelInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
