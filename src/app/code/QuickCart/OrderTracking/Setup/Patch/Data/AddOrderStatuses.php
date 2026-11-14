<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use QuickCart\OrderTracking\Model\Milestone;

/**
 * Custom order statuses for the fulfilment steps, assigned to the "processing" state
 * and visible to customers (Stores > Order Status lists them like native statuses).
 */
class AddOrderStatuses implements DataPatchInterface
{
    private const STATUSES = [
        Milestone::STATUS_PACKED => 'Packed',
        Milestone::STATUS_SHIPPED => 'Shipped',
        Milestone::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
    ];

    /**
     * @param StatusFactory $statusFactory
     * @param StatusResource $statusResource
     */
    public function __construct(
        private readonly StatusFactory $statusFactory,
        private readonly StatusResource $statusResource
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        foreach (self::STATUSES as $code => $label) {
            $status = $this->statusFactory->create();
            $this->statusResource->load($status, $code);
            if (!$status->getStatus()) {
                $status->setData(['status' => $code, 'label' => $label]);
                $this->statusResource->save($status);
            }
            $this->statusResource->assignState($code, Order::STATE_PROCESSING, false, true);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
