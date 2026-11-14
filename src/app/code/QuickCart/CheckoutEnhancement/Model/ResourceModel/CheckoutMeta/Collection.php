<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Model\ResourceModel\CheckoutMeta;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use QuickCart\CheckoutEnhancement\Model\CheckoutMeta;
use QuickCart\CheckoutEnhancement\Model\ResourceModel\CheckoutMeta as CheckoutMetaResource;

/**
 * Collection of checkout details.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(CheckoutMeta::class, CheckoutMetaResource::class);
    }
}
