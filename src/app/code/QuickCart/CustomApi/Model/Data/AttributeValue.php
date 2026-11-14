<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\AttributeValueInterface;

/**
 * A product specification (attribute code, store label and display value).
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class AttributeValue extends AbstractSimpleObject implements AttributeValueInterface
{
    /**
     * @inheritdoc
     */
    public function getCode(): string
    {
        return (string)$this->_get('code');
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
    public function getValue(): string
    {
        return (string)$this->_get('value');
    }
}
