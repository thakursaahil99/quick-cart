<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use DateTimeImmutable;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\CustomApi\Api\Data\OrderListInterface;
use QuickCart\CustomApi\Api\Data\OrderListInterfaceFactory;
use QuickCart\CustomApi\Api\Data\OrderStatsInterface;
use QuickCart\CustomApi\Api\Data\OrderStatsInterfaceFactory;
use QuickCart\CustomApi\Api\Data\OrderSummaryInterfaceFactory;
use QuickCart\CustomApi\Api\Data\StatusCountInterfaceFactory;
use QuickCart\CustomApi\Api\Data\TrackingInterface;
use QuickCart\CustomApi\Api\OrderServiceInterface;
use QuickCart\CustomApi\Model\ResourceModel\SalesQuery;
use QuickCart\OrderTracking\Model\Config as TrackingConfig;
use QuickCart\OrderTracking\Model\Milestone;
use QuickCart\OrderTracking\Model\OrderLookup;
use QuickCart\OrderTracking\Model\TimelineBuilder;
use QuickCart\OrderTracking\Model\TrackingUpdater;

/**
 * Order REST service.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderService implements OrderServiceInterface
{
    public const MAX_PAGE_SIZE = 50;

    /**
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderLookup $orderLookup
     * @param TrackingMapper $trackingMapper
     * @param TimelineBuilder $timelineBuilder
     * @param Milestone $milestone
     * @param TrackingUpdater $trackingUpdater
     * @param TrackingConfig $trackingConfig
     * @param GuestLookupLimiter $limiter
     * @param SalesQuery $salesQuery
     * @param DateRange $dateRange
     * @param TimezoneInterface $timezone
     * @param OrderConfig $orderConfig
     * @param StoreManagerInterface $storeManager
     * @param UserContextInterface $userContext
     * @param OrderListInterfaceFactory $orderListFactory
     * @param OrderSummaryInterfaceFactory $orderSummaryFactory
     * @param OrderStatsInterfaceFactory $orderStatsFactory
     * @param StatusCountInterfaceFactory $statusCountFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLookup $orderLookup,
        private readonly TrackingMapper $trackingMapper,
        private readonly TimelineBuilder $timelineBuilder,
        private readonly Milestone $milestone,
        private readonly TrackingUpdater $trackingUpdater,
        private readonly TrackingConfig $trackingConfig,
        private readonly GuestLookupLimiter $limiter,
        private readonly SalesQuery $salesQuery,
        private readonly DateRange $dateRange,
        private readonly TimezoneInterface $timezone,
        private readonly OrderConfig $orderConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly UserContextInterface $userContext,
        private readonly OrderListInterfaceFactory $orderListFactory,
        private readonly OrderSummaryInterfaceFactory $orderSummaryFactory,
        private readonly OrderStatsInterfaceFactory $orderStatsFactory,
        private readonly StatusCountInterfaceFactory $statusCountFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getCustomerOrders(int $customerId, int $page = 1, int $pageSize = 10): OrderListInterface
    {
        $page = max(1, $page);
        $pageSize = min(self::MAX_PAGE_SIZE, max(1, $pageSize));
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC')
            ->setOrder('entity_id', 'DESC')
            ->setPageSize($pageSize)
            ->setCurPage($page);
        $total = (int)$collection->getSize();

        $items = [];
        if (($page - 1) * $pageSize < $total) {
            /** @var Order $order */
            foreach ($collection as $order) {
                $milestone = $this->timelineBuilder->getCurrentMilestone($order);
                $items[] = $this->orderSummaryFactory->create(['data' => [
                    'order_number' => (string)$order->getIncrementId(),
                    'created_at' => $this->trackingMapper->iso((string)$order->getCreatedAt()),
                    'status' => (string)$order->getStatus(),
                    'status_label' => (string)($order->getFrontendStatusLabel() ?: $order->getStatusLabel()),
                    'milestone' => $milestone,
                    'milestone_label' => $this->milestone->getLabel($milestone),
                    'grand_total' => round((float)$order->getGrandTotal(), 2),
                    'currency' => (string)$order->getOrderCurrencyCode(),
                    'items_count' => (int)$order->getTotalItemCount(),
                ]]);
            }
        }

        return $this->orderListFactory->create(['data' => [
            'items' => $items,
            'total_count' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ]]);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerOrderTracking(int $customerId, string $orderNumber): TrackingInterface
    {
        $incrementId = $this->orderLookup->normalizeIncrementId($orderNumber);
        /** @var Order $order */
        $order = $this->orderCollectionFactory->create()
            ->addFieldToFilter('increment_id', $incrementId ?: '-')
            ->addFieldToFilter('customer_id', $customerId)
            ->setPageSize(1)
            ->getFirstItem();
        if (!$order->getId() || !$this->timelineBuilder->isEnabled($order)) {
            throw new NoSuchEntityException(__('The order "%1" was not found.', $orderNumber));
        }

        return $this->trackingMapper->map($order);
    }

    /**
     * @inheritdoc
     */
    public function trackGuestOrder(string $orderNumber, string $contact): TrackingInterface
    {
        if ($this->limiter->isLocked($this->trackingConfig->getMaxLookupAttempts())) {
            throw new WebapiException(
                __('Too many attempts. Please wait a few minutes and try again.'),
                0,
                429
            );
        }
        $order = $this->orderLookup->find(
            $orderNumber,
            $contact,
            (int)$this->storeManager->getStore()->getId()
        );
        if (!$order || !$this->timelineBuilder->isEnabled($order)) {
            $this->limiter->addFailure();
            throw new NoSuchEntityException(__(
                'We could not find an order with those details. Check the order number and the email or mobile number used at checkout.'
            ));
        }

        return $this->trackingMapper->map($order);
    }

    /**
     * @inheritdoc
     */
    public function getStats(?string $from = null, ?string $to = null): OrderStatsInterface
    {
        $today = DateTimeImmutable::createFromMutable($this->timezone->date());
        [$fromDay, $toDay] = $this->dateRange->resolve($from, $to, $today);
        $rows = $this->salesQuery->getStatusTotals(
            $this->timezone->convertConfigTimeToUtc($fromDay . ' 00:00:00'),
            $this->timezone->convertConfigTimeToUtc($toDay . ' 23:59:59')
        );

        $orders = 0;
        $paidOrders = 0;
        $revenue = 0.0;
        $statuses = [];
        foreach ($rows as $row) {
            $orders += $row['orders'];
            if ($row['state'] !== Order::STATE_CANCELED) {
                $paidOrders += $row['orders'];
                $revenue += $row['revenue'];
            }
            $statuses[] = $this->statusCountFactory->create(['data' => [
                'status' => $row['status'],
                'label' => (string)$this->orderConfig->getStatusLabel($row['status']),
                'order_count' => $row['orders'],
                'revenue' => round($row['revenue'], 2),
            ]]);
        }

        return $this->orderStatsFactory->create(['data' => [
            'from' => $fromDay,
            'to' => $toDay,
            'order_count' => $orders,
            'revenue' => round($revenue, 2),
            'average_order_value' => $paidOrders ? round($revenue / $paidOrders, 2) : 0.0,
            'currency' => (string)$this->storeManager->getStore()->getBaseCurrencyCode(),
            'statuses' => $statuses,
        ]]);
    }

    /**
     * @inheritdoc
     */
    public function addTrackingUpdate(
        int $orderId,
        string $milestone,
        ?string $title = null,
        ?string $comment = null,
        ?string $location = null,
        bool $visibleToCustomer = true,
        bool $updateStatus = false
    ): TrackingInterface {
        /** @var Order $order */
        $order = $this->orderRepository->get($orderId);
        $this->trackingUpdater->addUpdate(
            $order,
            $milestone,
            (string)$title,
            $comment,
            $location,
            $visibleToCustomer,
            $updateStatus,
            $this->getApiUser()
        );

        return $this->trackingMapper->map($order);
    }

    /**
     * "api:admin#1" / "api:integration#3" for the timeline's "created by".
     *
     * @return string
     */
    private function getApiUser(): string
    {
        $type = match ((int)$this->userContext->getUserType()) {
            UserContextInterface::USER_TYPE_ADMIN => 'admin',
            UserContextInterface::USER_TYPE_INTEGRATION => 'integration',
            default => 'user',
        };

        return sprintf('api:%s#%d', $type, (int)$this->userContext->getUserId());
    }
}
