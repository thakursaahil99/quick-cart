<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Model;

use Magento\Framework\Model\AbstractModel;
use QuickCart\CheckoutEnhancement\Api\Data\CheckoutMetaInterface;
use QuickCart\CheckoutEnhancement\Model\ResourceModel\CheckoutMeta as CheckoutMetaResource;

/**
 * Checkout details model.
 */
class CheckoutMeta extends AbstractModel implements CheckoutMetaInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'quickcart_checkout_meta';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(CheckoutMetaResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getQuoteId(): ?int
    {
        $value = $this->getData(self::QUOTE_ID);

        return $value === null ? null : (int)$value;
    }

    /**
     * @inheritdoc
     */
    public function setQuoteId(?int $quoteId): CheckoutMetaInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId(): ?int
    {
        $value = $this->getData(self::ORDER_ID);

        return $value === null ? null : (int)$value;
    }

    /**
     * @inheritdoc
     */
    public function setOrderId(?int $orderId): CheckoutMetaInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryNote(): ?string
    {
        $value = $this->getData(self::DELIVERY_NOTE);

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function setDeliveryNote(?string $note): CheckoutMetaInterface
    {
        return $this->setData(self::DELIVERY_NOTE, $note);
    }

    /**
     * @inheritdoc
     */
    public function getGstin(): ?string
    {
        $value = $this->getData(self::GSTIN);

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function setGstin(?string $gstin): CheckoutMetaInterface
    {
        return $this->setData(self::GSTIN, $gstin);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);

        return $value === null ? null : (string)$value;
    }
}
