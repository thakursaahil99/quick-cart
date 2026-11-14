<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use QuickCart\OrderTracking\Api\TrackingEventRepositoryInterface;
use QuickCart\OrderTracking\Model\Milestone;
use QuickCart\OrderTracking\Model\TrackingEventFactory;
use QuickCart\OrderTracking\Model\TrackingUpdater;

#[CoversClass(TrackingUpdater::class)]
class TrackingUpdaterTest extends TestCase
{
    public function testStatusMilestonesOnlyForNewAndProcessingOrders(): void
    {
        $updater = $this->updater();

        $this->assertSame(
            [Milestone::CONFIRMED, Milestone::PACKED, Milestone::SHIPPED, Milestone::OUT_FOR_DELIVERY],
            $updater->getStatusMilestones($this->order(Order::STATE_PROCESSING))
        );
        $this->assertSame([], $updater->getStatusMilestones($this->order(Order::STATE_COMPLETE)));
        $this->assertSame([], $updater->getStatusMilestones($this->order(Order::STATE_CANCELED)));
    }

    public function testCompletedOrdersCannotChangeStatusAndNothingIsSaved(): void
    {
        $events = $this->createMock(TrackingEventRepositoryInterface::class);
        $events->expects($this->never())->method('save');

        $this->expectException(LocalizedException::class);
        $this->updater($events)->addUpdate($this->order(Order::STATE_COMPLETE), Milestone::PACKED, '', null, null, true, true);
    }

    public function testMilestonesWithoutStatusCannotChangeStatus(): void
    {
        $this->expectException(LocalizedException::class);
        $this->updater()->addUpdate($this->order(Order::STATE_NEW), Milestone::DELIVERED, '', null, null, true, true);
    }

    private function updater(?TrackingEventRepositoryInterface $events = null): TrackingUpdater
    {
        return new TrackingUpdater(
            $this->createStub(OrderRepositoryInterface::class),
            $events ?? $this->createStub(TrackingEventRepositoryInterface::class),
            $this->createStub(TrackingEventFactory::class),
            new Milestone()
        );
    }

    private function order(string $state): Order
    {
        $order = $this->createStub(Order::class);
        $order->method('getState')->willReturn($state);

        return $order;
    }
}
