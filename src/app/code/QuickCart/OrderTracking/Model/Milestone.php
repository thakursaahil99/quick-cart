<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\Order;

/**
 * Tracking milestones and how Magento order states / statuses map onto them.
 */
class Milestone implements OptionSourceInterface
{
    public const PLACED = 'placed';
    public const CONFIRMED = 'confirmed';
    public const PACKED = 'packed';
    public const SHIPPED = 'shipped';
    public const OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERED = 'delivered';
    public const ON_HOLD = 'on_hold';
    public const CANCELED = 'canceled';
    public const CLOSED = 'closed';
    public const UPDATE = 'update';

    /** Custom order statuses (state "processing") added by Setup\Patch\Data\AddOrderStatuses. */
    public const STATUS_PACKED = 'qc_packed';
    public const STATUS_SHIPPED = 'qc_shipped';
    public const STATUS_OUT_FOR_DELIVERY = 'qc_out_for_delivery';

    /**
     * The progress steps shown to customers, in order.
     */
    public const STEPS = [
        self::PLACED,
        self::CONFIRMED,
        self::PACKED,
        self::SHIPPED,
        self::OUT_FOR_DELIVERY,
        self::DELIVERED,
    ];

    /**
     * Admin "update order status too" targets: milestone => order status (state processing).
     */
    public const STATUS_FOR_MILESTONE = [
        self::CONFIRMED => Order::STATE_PROCESSING,
        self::PACKED => self::STATUS_PACKED,
        self::SHIPPED => self::STATUS_SHIPPED,
        self::OUT_FOR_DELIVERY => self::STATUS_OUT_FOR_DELIVERY,
    ];

    /**
     * Milestone => label.
     *
     * @return array<string, string>
     */
    public function getLabels(): array
    {
        return [
            self::PLACED => (string)__('Order placed'),
            self::CONFIRMED => (string)__('Confirmed'),
            self::PACKED => (string)__('Packed'),
            self::SHIPPED => (string)__('Shipped'),
            self::OUT_FOR_DELIVERY => (string)__('Out for delivery'),
            self::DELIVERED => (string)__('Delivered'),
            self::ON_HOLD => (string)__('On hold'),
            self::CANCELED => (string)__('Cancelled'),
            self::CLOSED => (string)__('Refunded'),
            self::UPDATE => (string)__('Update'),
        ];
    }

    /**
     * Label of a milestone.
     *
     * @param string $milestone
     * @return string
     */
    public function getLabel(string $milestone): string
    {
        return $this->getLabels()[$milestone] ?? ucfirst(str_replace('_', ' ', $milestone));
    }

    /**
     * Whether the code is a known milestone.
     *
     * @param string $milestone
     * @return bool
     */
    public function isValid(string $milestone): bool
    {
        return array_key_exists($milestone, $this->getLabels());
    }

    /**
     * Milestone represented by an order state + status.
     *
     * @param string $state
     * @param string $status
     * @return string|null
     */
    public function fromOrderState(string $state, string $status): ?string
    {
        return match (true) {
            $status === self::STATUS_PACKED => self::PACKED,
            $status === self::STATUS_SHIPPED => self::SHIPPED,
            $status === self::STATUS_OUT_FOR_DELIVERY => self::OUT_FOR_DELIVERY,
            $state === Order::STATE_NEW,
            $state === Order::STATE_PENDING_PAYMENT,
            $state === Order::STATE_PAYMENT_REVIEW => self::PLACED,
            $state === Order::STATE_PROCESSING => self::CONFIRMED,
            $state === Order::STATE_COMPLETE => self::DELIVERED,
            $state === Order::STATE_HOLDED => self::ON_HOLD,
            $state === Order::STATE_CANCELED => self::CANCELED,
            $state === Order::STATE_CLOSED => self::CLOSED,
            default => null,
        };
    }

    /**
     * Index of a milestone on the progress bar, or -1 for off-track milestones.
     *
     * @param string $milestone
     * @return int
     */
    public function stepIndex(string $milestone): int
    {
        $index = array_search($milestone, self::STEPS, true);

        return $index === false ? -1 : (int)$index;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getLabels() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
