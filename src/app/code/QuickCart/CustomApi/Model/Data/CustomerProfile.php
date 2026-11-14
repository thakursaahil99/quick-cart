<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use QuickCart\CustomApi\Api\Data\CustomerProfileInterface;

/**
 * Customer account summary with order insights.
 *
 * REST output object; values are passed to the constructor as ['data' => [...]].
 */
class CustomerProfile extends AbstractSimpleObject implements CustomerProfileInterface
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
    public function getFirstname(): string
    {
        return (string)$this->_get('firstname');
    }

    /**
     * @inheritdoc
     */
    public function getLastname(): string
    {
        return (string)$this->_get('lastname');
    }

    /**
     * @inheritdoc
     */
    public function getEmail(): string
    {
        return (string)$this->_get('email');
    }

    /**
     * @inheritdoc
     */
    public function getGroup(): string
    {
        return (string)$this->_get('group');
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->_get('created_at');
    }

    /**
     * @inheritdoc
     */
    public function getAddressesCount(): int
    {
        return (int)$this->_get('addresses_count');
    }

    /**
     * @inheritdoc
     */
    public function getDefaultShippingArea(): ?string
    {
        $value = $this->_get('default_shipping_area');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getOrderCount(): int
    {
        return (int)$this->_get('order_count');
    }

    /**
     * @inheritdoc
     */
    public function getLifetimeValue(): float
    {
        return (float)$this->_get('lifetime_value');
    }

    /**
     * @inheritdoc
     */
    public function getAverageOrderValue(): float
    {
        return (float)$this->_get('average_order_value');
    }

    /**
     * @inheritdoc
     */
    public function getLastOrderAt(): ?string
    {
        $value = $this->_get('last_order_at');

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getWishlistItems(): int
    {
        return (int)$this->_get('wishlist_items');
    }

    /**
     * @inheritdoc
     */
    public function getCurrency(): string
    {
        return (string)$this->_get('currency');
    }
}
