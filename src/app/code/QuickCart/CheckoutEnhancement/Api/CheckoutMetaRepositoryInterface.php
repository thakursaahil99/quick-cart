<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\CheckoutEnhancement\Api\Data\CheckoutMetaInterface;

/**
 * Persistence for checkout details.
 *
 * @api
 */
interface CheckoutMetaRepositoryInterface
{
    /**
     * Save checkout details.
     *
     * @param CheckoutMetaInterface $meta
     * @return CheckoutMetaInterface
     * @throws CouldNotSaveException
     */
    public function save(CheckoutMetaInterface $meta): CheckoutMetaInterface;

    /**
     * Details captured for a quote.
     *
     * @param int $quoteId
     * @return CheckoutMetaInterface
     * @throws NoSuchEntityException
     */
    public function getByQuoteId(int $quoteId): CheckoutMetaInterface;

    /**
     * Details attached to an order.
     *
     * @param int $orderId
     * @return CheckoutMetaInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): CheckoutMetaInterface;

    /**
     * Details for several orders at once (order grid / API lists), keyed by order ID.
     *
     * @param int[] $orderIds
     * @return array<int, CheckoutMetaInterface>
     */
    public function getByOrderIds(array $orderIds): array;
}
