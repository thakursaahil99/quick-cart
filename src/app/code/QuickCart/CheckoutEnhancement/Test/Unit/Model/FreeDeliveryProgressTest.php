<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use QuickCart\CheckoutEnhancement\Model\FreeDeliveryProgress;

#[CoversClass(FreeDeliveryProgress::class)]
class FreeDeliveryProgressTest extends TestCase
{
    public function testHalfwayToThreshold(): void
    {
        $result = $this->createProgress(true, '1000')->calculate(400.0);

        $this->assertTrue($result['enabled']);
        $this->assertFalse($result['unlocked']);
        $this->assertSame(40, $result['percent']);
        $this->assertSame(600.0, $result['remaining']);
        $this->assertSame('Rs600.00', $result['remaining_formatted']);
    }

    public function testUnlockedAtOrAboveThreshold(): void
    {
        $result = $this->createProgress(true, '999')->calculate(1499.0);

        $this->assertTrue($result['unlocked']);
        $this->assertSame(100, $result['percent']);
        $this->assertSame(0.0, $result['remaining']);
    }

    public function testDisabledWhenCarrierInactiveOrNoThreshold(): void
    {
        $this->assertFalse($this->createProgress(false, '999')->calculate(10.0)['enabled']);
        $this->assertFalse($this->createProgress(true, '0')->calculate(10.0)['enabled']);
    }

    private function createProgress(bool $active, string $threshold): FreeDeliveryProgress
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn($active);
        $config->method('getValue')->willReturn($threshold);

        $currency = $this->createStub(PriceCurrencyInterface::class);
        $currency->method('format')->willReturnCallback(
            static fn (float $amount): string => 'Rs' . number_format($amount, 2, '.', '')
        );

        return new FreeDeliveryProgress($config, $currency);
    }
}
