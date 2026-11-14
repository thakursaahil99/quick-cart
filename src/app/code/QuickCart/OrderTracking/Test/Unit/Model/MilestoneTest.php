<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Test\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use QuickCart\OrderTracking\Api\TrackingEventRepositoryInterface;
use QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent as TrackingEventResource;
use QuickCart\OrderTracking\Model\TrackingEventFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\OrderTracking\Model\EventRecorder;
use QuickCart\OrderTracking\Model\Milestone;

#[CoversClass(Milestone::class)]
#[CoversClass(EventRecorder::class)]
class MilestoneTest extends TestCase
{
    #[DataProvider('stateProvider')]
    public function testOrderStateMapping(string $state, string $status, ?string $expected): void
    {
        $this->assertSame($expected, (new Milestone())->fromOrderState($state, $status));
    }

    /**
     * @return array<string, array{string, string, string|null}>
     */
    public static function stateProvider(): array
    {
        return [
            'new order' => ['new', 'pending', Milestone::PLACED],
            'processing' => ['processing', 'processing', Milestone::CONFIRMED],
            'custom packed status' => ['processing', 'qc_packed', Milestone::PACKED],
            'custom out for delivery' => ['processing', 'qc_out_for_delivery', Milestone::OUT_FOR_DELIVERY],
            'complete' => ['complete', 'complete', Milestone::DELIVERED],
            'cancelled' => ['canceled', 'canceled', Milestone::CANCELED],
            'refunded' => ['closed', 'closed', Milestone::CLOSED],
            'unknown' => ['mystery', 'mystery', null],
        ];
    }

    public function testStepIndex(): void
    {
        $this->assertSame(0, (new Milestone())->stepIndex(Milestone::PLACED));
        $this->assertSame(5, (new Milestone())->stepIndex(Milestone::DELIVERED));
        $this->assertSame(-1, (new Milestone())->stepIndex(Milestone::CANCELED));
    }

    #[DataProvider('recordProvider')]
    public function testStatusEventsOnlyMoveForward(string $milestone, array $recorded, bool $expected): void
    {
        $this->assertSame($expected, $this->recorder()->shouldRecord($milestone, $recorded));
    }

    /**
     * @return array<string, array{string, string[], bool}>
     */
    public static function recordProvider(): array
    {
        return [
            'first event' => [Milestone::PLACED, [], true],
            'same milestone again' => [Milestone::PLACED, [Milestone::PLACED], false],
            'forward' => [Milestone::PACKED, [Milestone::PLACED, Milestone::CONFIRMED], true],
            // ShipOrder resets the status to "processing" after an admin "Shipped" update.
            'reset to processing after shipping' => [Milestone::CONFIRMED, [Milestone::PLACED, Milestone::SHIPPED], false],
            'delivered after shipping' => [Milestone::DELIVERED, [Milestone::PLACED, Milestone::SHIPPED], true],
            'hold is recorded' => [Milestone::ON_HOLD, [Milestone::PLACED, Milestone::CONFIRMED], true],
            'hold twice is not' => [Milestone::ON_HOLD, [Milestone::PLACED, Milestone::ON_HOLD], false],
        ];
    }

    private function recorder(): EventRecorder
    {
        return new EventRecorder(
            $this->createStub(TrackingEventRepositoryInterface::class),
            $this->createStub(TrackingEventFactory::class),
            $this->createStub(TrackingEventResource::class),
            new Milestone(),
            $this->createStub(LoggerInterface::class)
        );
    }
}
