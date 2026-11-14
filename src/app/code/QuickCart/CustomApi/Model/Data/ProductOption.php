<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\ProductOptionInterface;

/**
 * Configurable option the shopper chooses (e.g. Colour: Midnight, Silver).
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class ProductOption extends AbstractSimpleObject implements ProductOptionInterface
{
    /**
     * @inheritdoc
     */
    public function getAttributeCode(): string
    {
        return (string)$this->_get('attribute_code');
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string)$this->_get('label');
    }

    /**
     * @inheritdoc
     */
    public function getValues(): array
    {
        return (array)$this->_get('values');
    }
}
