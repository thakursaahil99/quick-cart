<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Test\Unit\Model;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\OrderTracking\Model\OrderLookup;

#[CoversClass(OrderLookup::class)]
class OrderLookupTest extends TestCase
{
    public function testIncrementIdNormalisation(): void
    {
        $this->assertSame('000000002', $this->lookup()->normalizeIncrementId(' #000000002 '));
        $this->assertSame('', $this->lookup()->normalizeIncrementId("1' OR '1'='1"));
        $this->assertSame('', $this->lookup()->normalizeIncrementId(''));
    }

    public function testPhoneNormalisation(): void
    {
        $this->assertSame('9876543210', $this->lookup()->normalizePhone('+91 98765-43210'));
        $this->assertSame('9876543210', $this->lookup()->normalizePhone('09876543210'));
        $this->assertSame('', $this->lookup()->normalizePhone('12345'));
    }

    #[DataProvider('contactProvider')]
    public function testContactMatching(string $entered, bool $expected): void
    {
        $this->assertSame(
            $expected,
            $this->lookup()->contactMatches($entered, 'Ravi.Demo@example.com', ['98200 12345', ''])
        );
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function contactProvider(): array
    {
        return [
            'email, any case' => [' ravi.demo@EXAMPLE.com ', true],
            'other email' => ['ravi@example.com', false],
            'phone with country code' => ['+91-9820012345', true],
            'phone digits differ' => ['9820012346', false],
            'too short to be a phone' => ['12345', false],
            'empty' => ['', false],
        ];
    }

    private function lookup(): OrderLookup
    {
        return new OrderLookup($this->createStub(CollectionFactory::class));
    }
}
