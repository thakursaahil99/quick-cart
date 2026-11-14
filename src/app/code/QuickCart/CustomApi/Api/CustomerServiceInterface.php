<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api;

use QuickCart\CustomApi\Api\Data\CustomerProfileInterface;

/**
 * Customer profile with order insights.
 *
 * @api
 */
interface CustomerServiceInterface
{
    /**
     * Profile of a customer (own profile for customer tokens, any customer for admins).
     *
     * @param int $customerId
     * @return \QuickCart\CustomApi\Api\Data\CustomerProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProfile(int $customerId): CustomerProfileInterface;
}
