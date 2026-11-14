<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\CategoryNodeInterface;

/**
 * Category in the storefront tree.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class CategoryNode extends AbstractSimpleObject implements CategoryNodeInterface
{
    /**
     * @inheritdoc
     */
    public function getId(): int
    {
        return (int)$this->_get('id');
    }

    /**
     * @inheritdoc
     */
    public function getParentId(): int
    {
        return (int)$this->_get('parent_id');
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return (string)$this->_get('name');
    }

    /**
     * @inheritdoc
     */
    public function getUrlKey(): ?string
    {
        $value = $this->_get('url_key');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): string
    {
        return (string)$this->_get('url');
    }

    /**
     * @inheritdoc
     */
    public function getImageUrl(): ?string
    {
        $value = $this->_get('image_url');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getLevel(): int
    {
        return (int)$this->_get('level');
    }

    /**
     * @inheritdoc
     */
    public function getPosition(): int
    {
        return (int)$this->_get('position');
    }

    /**
     * @inheritdoc
     */
    public function getProductCount(): int
    {
        return (int)$this->_get('product_count');
    }

    /**
     * @inheritdoc
     */
    public function getChildren(): array
    {
        return (array)$this->_get('children');
    }
}
