<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Test\Unit\Model\Report;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\SalesDashboard\Model\Report\Dashboard;
use QuickCart\SalesDashboard\Model\Report\Money;
use QuickCart\SalesDashboard\Model\Report\Reports;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesAnalytics;

#[CoversClass(Money::class)]
#[CoversClass(Reports::class)]
class MoneyAndReportsTest extends TestCase
{
    #[DataProvider('compactProvider')]
    public function testCompactNumbers(float $value, bool $indian, string $expected): void
    {
        $this->assertSame($expected, $this->money()->compactNumber($value, $indian));
    }

    /**
     * @return array<string, array{float, bool, string}>
     */
    public static function compactProvider(): array
    {
        return [
            'lakh' => [1147606.0, true, '11.5 L'],
            'crore' => [12500000.0, true, '1.25 Cr'],
            'whole lakh, no trailing zero' => [500000.0, true, '5 L'],
            'hundreds of thousands stay whole' => [120000.0, false, '120K'],
            'million' => [4200000.0, false, '4.2M'],
            'small' => [999.0, true, '999'],
        ];
    }

    public function testPercentChange(): void
    {
        $money = $this->money();

        $this->assertSame(101.2, $money->change(1828006.0, 908456.0));
        $this->assertSame(-25.0, $money->change(75.0, 100.0));
        $this->assertNull($money->change(10.0, 0.0));
    }

    public function testCsvQuotesValuesAndNeutralisesFormulas(): void
    {
        $reports = new Reports($this->createStub(Dashboard::class), $this->createStub(SalesAnalytics::class));
        $csv = $reports->toCsv(['SKU', 'Product', 'Revenue'], [
            ['QC-1', '=HYPERLINK("http://evil")', -12.5],
            ['QC-2', 'Buds "Pro" 2', 1200],
        ]);

        $this->assertSame(
            "\"SKU\",\"Product\",\"Revenue\"\r\n"
            . "\"QC-1\",\"'=HYPERLINK(\"\"http://evil\"\")\",\"-12.5\"\r\n"
            . "\"QC-2\",\"Buds \"\"Pro\"\" 2\",\"1200\"\r\n",
            $csv
        );
    }

    private function money(): Money
    {
        return new Money($this->createStub(PriceCurrencyInterface::class));
    }
}
