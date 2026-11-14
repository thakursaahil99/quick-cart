<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\TrackingStepInterface;

/**
 * One step of the delivery progress bar.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class TrackingStep extends AbstractSimpleObject implements TrackingStepInterface
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
    public function getState(): string
    {
        return (string)$this->_get('state');
    }

    /**
     * @inheritdoc
     */
    public function getReachedAt(): ?string
    {
        $value = $this->_get('reached_at');

        return $value === null ? null : (string)$value;
    }
}
