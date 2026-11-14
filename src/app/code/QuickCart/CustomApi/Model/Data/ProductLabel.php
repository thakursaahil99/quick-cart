<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\ProductLabelInterface;

/**
 * Product label (QuickCart label rules) as shown on product cards.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class ProductLabel extends AbstractSimpleObject implements ProductLabelInterface
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
    public function getText(): string
    {
        return (string)$this->_get('text');
    }

    /**
     * @inheritdoc
     */
    public function getBackgroundColor(): string
    {
        return (string)$this->_get('background_color');
    }

    /**
     * @inheritdoc
     */
    public function getTextColor(): string
    {
        return (string)$this->_get('text_color');
    }
}
