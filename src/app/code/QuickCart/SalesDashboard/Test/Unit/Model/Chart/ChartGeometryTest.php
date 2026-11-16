<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Test\Unit\Model\Chart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\SalesDashboard\Model\Chart\ChartGeometry;

#[CoversClass(ChartGeometry::class)]
class ChartGeometryTest extends TestCase
{
    #[DataProvider('tickProvider')]
    public function testNiceTicksStartAtZeroAndCoverTheMaximum(float $max, int $count, array $expected): void
    {
        $this->assertSame($expected, (new ChartGeometry())->niceTicks($max, $count));
    }

    /**
     * @return array<string, array{float, int, float[]}>
     */
    public static function tickProvider(): array
    {
        return [
            'revenue' => [126229.0, 4, [0.0, 50000.0, 100000.0, 150000.0]],
            'small counts' => [5.0, 3, [0.0, 2.0, 4.0, 6.0]],
            'exact top' => [100.0, 4, [0.0, 25.0, 50.0, 75.0, 100.0]],
            'no data' => [0.0, 4, [0.0, 1.0]],
        ];
    }

    public function testLineSpansTheBoxAndClosesTheArea(): void
    {
        $box = ['x' => 10.0, 'y' => 0.0, 'width' => 100.0, 'height' => 50.0];
        $line = (new ChartGeometry())->line([0, 50, 100], $box, 100.0);

        $this->assertSame([['x' => 10.0, 'y' => 50.0], ['x' => 60.0, 'y' => 25.0], ['x' => 110.0, 'y' => 0.0]], $line['points']);
        $this->assertSame('M10 50 L60 25 L110 0', $line['line']);
        $this->assertStringEndsWith('L110 50 L10 50 Z', $line['area']);
    }

    public function testColumnsAreCappedAtTwentyFourPixelsAndSkipZeroes(): void
    {
        $box = ['x' => 0.0, 'y' => 0.0, 'width' => 300.0, 'height' => 100.0];
        $columns = (new ChartGeometry())->columns([4, 0, 2], $box, 4.0);

        $this->assertCount(3, $columns);
        $this->assertSame(24.0, $columns[0]['width']);
        $this->assertSame(50.0, $columns[0]['center']);
        $this->assertSame('', $columns[1]['path']);
        $this->assertStringStartsWith('M38 100 V4', $columns[0]['path']);
    }

    public function testLabelIndexesKeepFirstAndLast(): void
    {
        $geometry = new ChartGeometry();

        $this->assertSame([0, 6, 12, 17, 23, 29], $geometry->labelIndexes(30));
        $this->assertSame([0, 1, 2], $geometry->labelIndexes(3));
    }
}
