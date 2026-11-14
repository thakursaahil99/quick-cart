<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Finds an order for the public tracking form: order number + the email address or
 * mobile number used at checkout. Both must match, so order numbers alone reveal nothing.
 */
class OrderLookup
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Order matching the number and contact in the store, or null.
     *
     * @param string $incrementId
     * @param string $contact Email address or mobile number
     * @param int $storeId
     * @return Order|null
     */
    public function find(string $incrementId, string $contact, int $storeId): ?Order
    {
        $incrementId = $this->normalizeIncrementId($incrementId);
        if ($incrementId === '' || trim($contact) === '') {
            return null;
        }
        /** @var Order $order */
        $order = $this->collectionFactory->create()
            ->addFieldToFilter('increment_id', $incrementId)
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize(1)
            ->getFirstItem();
        if (!$order->getId()) {
            return null;
        }
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        $phones = array_filter([
            $billing ? (string)$billing->getTelephone() : '',
            $shipping ? (string)$shipping->getTelephone() : '',
        ]);

        return $this->contactMatches($contact, (string)$order->getCustomerEmail(), $phones) ? $order : null;
    }

    /**
     * "#000000002", " 000000002 " -> "000000002".
     *
     * @param string $incrementId
     * @return string
     */
    public function normalizeIncrementId(string $incrementId): string
    {
        $incrementId = ltrim(trim($incrementId), '#');

        return preg_match('/^[A-Za-z0-9_-]{1,50}$/', $incrementId) ? $incrementId : '';
    }

    /**
     * Last 10 digits of an Indian mobile number ("+91 98765-43210" -> "9876543210").
     *
     * @param string $phone
     * @return string
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($digits) >= 10 ? substr($digits, -10) : '';
    }

    /**
     * Whether the entered contact equals the order email or one of its phone numbers.
     *
     * @param string $contact
     * @param string $email
     * @param string[] $phones
     * @return bool
     */
    public function contactMatches(string $contact, string $email, array $phones): bool
    {
        $contact = trim($contact);
        if (str_contains($contact, '@')) {
            return $email !== '' && hash_equals(mb_strtolower(trim($email)), mb_strtolower($contact));
        }
        $entered = $this->normalizePhone($contact);
        if ($entered === '') {
            return false;
        }
        foreach ($phones as $phone) {
            $known = $this->normalizePhone($phone);
            if ($known !== '' && hash_equals($known, $entered)) {
                return true;
            }
        }

        return false;
    }
}
