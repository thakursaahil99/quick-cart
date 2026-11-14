<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\ProductCardInterface;
use QuickCart\CustomApi\Api\Data\ProductDetailInterface;

/**
 * Product page data.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class ProductDetail extends AbstractSimpleObject implements ProductDetailInterface
{
    /**
     * @inheritdoc
     */
    public function getProduct(): ProductCardInterface
    {
        return $this->_get('product');
    }

    /**
     * @inheritdoc
     */
    public function getShortDescription(): ?string
    {
        $value = $this->_get('short_description');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        $value = $this->_get('description');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getSpecifications(): array
    {
        return (array)$this->_get('specifications');
    }

    /**
     * @inheritdoc
     */
    public function getImages(): array
    {
        return (array)$this->_get('images');
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): array
    {
        return (array)$this->_get('options');
    }

    /**
     * @inheritdoc
     */
    public function getCategoryIds(): array
    {
        return (array)$this->_get('category_ids');
    }

    /**
     * @inheritdoc
     */
    public function getRelatedSkus(): array
    {
        return (array)$this->_get('related_skus');
    }

    /**
     * @inheritdoc
     */
    public function getUpsellSkus(): array
    {
        return (array)$this->_get('upsell_skus');
    }
}
