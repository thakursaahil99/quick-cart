<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\ProductListInterface;

/**
 * A page of product cards.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class ProductList extends AbstractSimpleObject implements ProductListInterface
{
    /**
     * @inheritdoc
     */
    public function getItems(): array
    {
        return (array)$this->_get('items');
    }

    /**
     * @inheritdoc
     */
    public function getTotalCount(): int
    {
        return (int)$this->_get('total_count');
    }

    /**
     * @inheritdoc
     */
    public function getPage(): int
    {
        return (int)$this->_get('page');
    }

    /**
     * @inheritdoc
     */
    public function getPageSize(): int
    {
        return (int)$this->_get('page_size');
    }

    /**
     * @inheritdoc
     */
    public function getTotalPages(): int
    {
        return (int)$this->_get('total_pages');
    }
}
