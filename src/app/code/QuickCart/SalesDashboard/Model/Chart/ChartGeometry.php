<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\Chart;

/**
 * Server-side SVG geometry for the dashboard charts (no chart library, no external assets).
 *
 * Follows the QuickCart chart spec: one y-axis starting at zero with clean ticks, 2px
 * lines with a ~10% area wash, columns at most 24px wide with a 4px rounded data end and
 * a square baseline end.
 */
class ChartGeometry
{
    public const MAX_BAR = 24.0;
    public const RADIUS = 4.0;

    /**
     * Clean axis ticks from zero covering $max (steps of 1, 2, 2.5 or 5 × 10^n).
     *
     * @param float $max
     * @param int $count Desired number of intervals
     * @return float[]
     */
    public function niceTicks(float $max, int $count = 4): array
    {
        if ($max <= 0) {
            return [0.0, 1.0];
        }
        $raw = $max / max(1, $count);
        $magnitude = 10 ** floor(log10($raw));
        $step = $magnitude;
        foreach ([1, 2, 2.5, 5, 10] as $factor) {
            if ($factor * $magnitude >= $raw) {
                $step = $factor * $magnitude;
                break;
            }
        }
        $ticks = [];
        for ($value = 0.0; $value < $max + $step / 2; $value += $step) {
            $ticks[] = round($value, 6);
            if ($value >= $max) {
                break;
            }
        }
        if (end($ticks) < $max) {
            $ticks[] = round(end($ticks) + $step, 6);
        }

        return $ticks;
    }

    /**
     * Line + area for a series over a plot box.
     *
     * @param float[] $values In x order
     * @param array{x: float, y: float, width: float, height: float} $box
     * @param float $top Axis maximum (last tick)
     * @return array{points: array<int, array{x: float, y: float}>, line: string, area: string}
     */
    public function line(array $values, array $box, float $top): array
    {
        $values = array_values($values);
        $count = count($values);
        $points = [];
        foreach ($values as $index => $value) {
            $x = $count > 1 ? $box['x'] + $box['width'] * $index / ($count - 1) : $box['x'] + $box['width'] / 2;
            $y = $box['y'] + $box['height'] - ($top > 0 ? $box['height'] * max(0.0, (float)$value) / $top : 0);
            $points[] = ['x' => round($x, 2), 'y' => round($y, 2)];
        }
        if (!$points) {
            return ['points' => [], 'line' => '', 'area' => ''];
        }
        $line = 'M' . implode(' L', array_map(static fn (array $p) => $p['x'] . ' ' . $p['y'], $points));
        $baseline = round($box['y'] + $box['height'], 2);
        $area = $line . sprintf(' L%s %s L%s %s Z', end($points)['x'], $baseline, $points[0]['x'], $baseline);

        return ['points' => $points, 'line' => $line, 'area' => $area];
    }

    /**
     * Columns (one per value) with a rounded top and a square baseline.
     *
     * @param float[] $values
     * @param array{x: float, y: float, width: float, height: float} $box
     * @param float $top Axis maximum
     * @return array<int, array{x: float, width: float, center: float, path: string}>
     */
    public function columns(array $values, array $box, float $top): array
    {
        $values = array_values($values);
        $count = max(1, count($values));
        $slot = $box['width'] / $count;
        $width = min(self::MAX_BAR, max(2.0, $slot - 2.0));
        $baseline = $box['y'] + $box['height'];
        $columns = [];
        foreach ($values as $index => $value) {
            $height = $top > 0 ? $box['height'] * max(0.0, (float)$value) / $top : 0.0;
            $x = $box['x'] + $slot * $index + ($slot - $width) / 2;
            $columns[] = [
                'x' => round($x, 2),
                'width' => round($width, 2),
                'center' => round($x + $width / 2, 2),
                'path' => $height > 0 ? $this->roundedTop($x, $baseline, $width, $height) : '',
            ];
        }

        return $columns;
    }

    /**
     * Which indexes get an x-axis label (first, last and evenly spaced in between).
     *
     * @param int $count
     * @param int $maxLabels
     * @return int[]
     */
    public function labelIndexes(int $count, int $maxLabels = 6): array
    {
        if ($count <= $maxLabels) {
            return range(0, max(0, $count - 1));
        }
        $indexes = [];
        for ($i = 0; $i < $maxLabels; $i++) {
            $indexes[] = (int)round($i * ($count - 1) / ($maxLabels - 1));
        }

        return array_values(array_unique($indexes));
    }

    /**
     * SVG path of a column with 4px rounded top corners and a square bottom.
     *
     * @param float $x
     * @param float $baseline
     * @param float $width
     * @param float $height
     * @return string
     */
    private function roundedTop(float $x, float $baseline, float $width, float $height): string
    {
        $radius = min(self::RADIUS, $width / 2, $height);
        $top = $baseline - $height;
        $f = static fn (float $n) => round($n, 2);

        return sprintf(
            'M%s %s V%s Q%s %s %s %s H%s Q%s %s %s %s V%s Z',
            $f($x),
            $f($baseline),
            $f($top + $radius),
            $f($x),
            $f($top),
            $f($x + $radius),
            $f($top),
            $f($x + $width - $radius),
            $f($x + $width),
            $f($top),
            $f($x + $width),
            $f($top + $radius),
            $f($baseline)
        );
    }
}
