<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\TrackingNumberInterface;

/**
 * Carrier tracking number of a shipment.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class TrackingNumber extends AbstractSimpleObject implements TrackingNumberInterface
{
    /**
     * @inheritdoc
     */
    public function getCarrier(): string
    {
        return (string)$this->_get('carrier');
    }

    /**
     * @inheritdoc
     */
    public function getNumber(): string
    {
        return (string)$this->_get('number');
    }
}
