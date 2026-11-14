<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\ProductCardInterface;

/**
 * Product as shown on a storefront card.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class ProductCard extends AbstractSimpleObject implements ProductCardInterface
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
    public function getSku(): string
    {
        return (string)$this->_get('sku');
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
    public function getType(): string
    {
        return (string)$this->_get('type');
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
    public function getBrand(): ?string
    {
        $value = $this->_get('brand');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): float
    {
        return (float)$this->_get('price');
    }

    /**
     * @inheritdoc
     */
    public function getFinalPrice(): float
    {
        return (float)$this->_get('final_price');
    }

    /**
     * @inheritdoc
     */
    public function getDiscountPercent(): int
    {
        return (int)$this->_get('discount_percent');
    }

    /**
     * @inheritdoc
     */
    public function getCurrency(): string
    {
        return (string)$this->_get('currency');
    }

    /**
     * @inheritdoc
     */
    public function getRatingPercent(): ?int
    {
        $value = $this->_get('rating_percent');

        return $value === null ? null : (int)$value;
    }

    /**
     * @inheritdoc
     */
    public function getReviewCount(): int
    {
        return (int)$this->_get('review_count');
    }

    /**
     * @inheritdoc
     */
    public function getIsInStock(): bool
    {
        return (bool)$this->_get('is_in_stock');
    }

    /**
     * @inheritdoc
     */
    public function getLabels(): array
    {
        return (array)$this->_get('labels');
    }
}
