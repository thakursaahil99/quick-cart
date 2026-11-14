<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Api\Data;

/**
 * Checkout details captured for a quote and carried over to its order.
 *
 * @api
 */
interface CheckoutMetaInterface
{
    public const ENTITY_ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
    public const ORDER_ID = 'order_id';
    public const DELIVERY_NOTE = 'delivery_note';
    public const GSTIN = 'gstin';
    public const CREATED_AT = 'created_at';

    /**
     * Entity ID.
     *
     * @return int|null
     */
    public function getId();

    /**
     * Quote ID.
     *
     * @return int|null
     */
    public function getQuoteId(): ?int;

    /**
     * Set quote ID.
     *
     * @param int|null $quoteId
     * @return $this
     */
    public function setQuoteId(?int $quoteId): self;

    /**
     * Order ID.
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * Set order ID.
     *
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId): self;

    /**
     * Delivery instructions.
     *
     * @return string|null
     */
    public function getDeliveryNote(): ?string;

    /**
     * Set delivery instructions.
     *
     * @param string|null $note
     * @return $this
     */
    public function setDeliveryNote(?string $note): self;

    /**
     * GSTIN for a business (B2B) invoice.
     *
     * @return string|null
     */
    public function getGstin(): ?string;

    /**
     * Set GSTIN.
     *
     * @param string|null $gstin
     * @return $this
     */
    public function setGstin(?string $gstin): self;

    /**
     * Creation time.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
