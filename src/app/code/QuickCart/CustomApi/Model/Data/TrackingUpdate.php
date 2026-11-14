<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\TrackingUpdateInterface;

/**
 * A customer-visible timeline update.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class TrackingUpdate extends AbstractSimpleObject implements TrackingUpdateInterface
{
    /**
     * @inheritdoc
     */
    public function getMilestone(): string
    {
        return (string)$this->_get('milestone');
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return (string)$this->_get('title');
    }

    /**
     * @inheritdoc
     */
    public function getComment(): ?string
    {
        $value = $this->_get('comment');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getLocation(): ?string
    {
        $value = $this->_get('location');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->_get('created_at');
    }
}
