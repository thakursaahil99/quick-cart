<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\WishlistFactory;
use QuickCart\CustomApi\Api\CustomerServiceInterface;
use QuickCart\CustomApi\Api\Data\CustomerProfileInterface;
use QuickCart\CustomApi\Api\Data\CustomerProfileInterfaceFactory;
use QuickCart\CustomApi\Model\ResourceModel\SalesQuery;

/**
 * Customer profile REST service.
 */
class CustomerService implements CustomerServiceInterface
{
    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param GroupRepositoryInterface $groupRepository
     * @param WishlistFactory $wishlistFactory
     * @param SalesQuery $salesQuery
     * @param StoreManagerInterface $storeManager
     * @param TrackingMapper $trackingMapper
     * @param CustomerProfileInterfaceFactory $profileFactory
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly WishlistFactory $wishlistFactory,
        private readonly SalesQuery $salesQuery,
        private readonly StoreManagerInterface $storeManager,
        private readonly TrackingMapper $trackingMapper,
        private readonly CustomerProfileInterfaceFactory $profileFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getProfile(int $customerId): CustomerProfileInterface
    {
        $customer = $this->customerRepository->getById($customerId);

        try {
            $group = (string)$this->groupRepository->getById((int)$customer->getGroupId())->getCode();
        } catch (LocalizedException $e) {
            $group = '';
        }

        $area = null;
        $addresses = $customer->getAddresses() ?? [];
        foreach ($addresses as $address) {
            if ((string)$address->getId() === (string)$customer->getDefaultShipping()) {
                $area = implode(', ', array_filter([
                    (string)$address->getCity(),
                    (string)$address->getRegion()?->getRegion(),
                    (string)$address->getPostcode(),
                ])) ?: null;
            }
        }

        $totals = $this->salesQuery->getCustomerTotals($customerId);
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId);

        return $this->profileFactory->create(['data' => [
            'id' => (int)$customer->getId(),
            'firstname' => (string)$customer->getFirstname(),
            'lastname' => (string)$customer->getLastname(),
            'email' => (string)$customer->getEmail(),
            'group' => $group,
            'created_at' => $this->trackingMapper->iso((string)$customer->getCreatedAt()),
            'addresses_count' => count($addresses),
            'default_shipping_area' => $area,
            'order_count' => $totals['orders'],
            'lifetime_value' => round($totals['revenue'], 2),
            'average_order_value' => $totals['orders'] ? round($totals['revenue'] / $totals['orders'], 2) : 0.0,
            'last_order_at' => $totals['last_order_at'] ? $this->trackingMapper->iso($totals['last_order_at']) : null,
            'wishlist_items' => $wishlist->getId() ? (int)$wishlist->getItemsCount() : 0,
            'currency' => (string)$this->storeManager->getStore()->getBaseCurrencyCode(),
        ]]);
    }
}
