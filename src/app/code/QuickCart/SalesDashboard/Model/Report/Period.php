<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\Report;

use DateTimeImmutable;
use QuickCart\SalesDashboard\Model\Source\RangePreset;

/**
 * Resolves a report period (store-timezone days) and the equally long period before it,
 * used for period-over-period change.
 */
class Period
{
    public const MAX_DAYS = 366;

    /**
     * @param RangePreset $presets
     */
    public function __construct(
        private readonly RangePreset $presets
    ) {
    }

    /**
     * Period for a preset or a custom from/to; invalid input falls back to $fallback.
     *
     * @param string $preset
     * @param string|null $from Y-m-d (custom)
     * @param string|null $to Y-m-d (custom)
     * @param DateTimeImmutable $today
     * @param string $fallback
     * @return array{preset: string, from: string, to: string, days: int, prev_from: string, prev_to: string, label: string}
     */
    public function resolve(
        string $preset,
        ?string $from,
        ?string $to,
        DateTimeImmutable $today,
        string $fallback = RangePreset::LAST_30
    ): array {
        $today = $today->setTime(0, 0);
        $custom = $this->parse($from) && $this->parse($to) ? [$this->parse($from), $this->parse($to)] : null;
        if ($preset === RangePreset::CUSTOM && $custom && $custom[0] <= $custom[1]
            && $custom[0]->diff($custom[1])->days < self::MAX_DAYS) {
            [$start, $end] = $custom;
            $label = (string)__('%1 – %2', $start->format('j M Y'), $end->format('j M Y'));
        } else {
            $preset = array_key_exists($preset, $this->presets->getPresets()) ? $preset : $fallback;
            $end = $today;
            $start = match ($preset) {
                RangePreset::TODAY => $today,
                RangePreset::LAST_7 => $today->modify('-6 days'),
                RangePreset::LAST_90 => $today->modify('-89 days'),
                RangePreset::MONTH_TO_DATE => $today->modify('first day of this month'),
                default => $today->modify('-29 days'),
            };
            $label = $this->presets->getPresets()[$preset];
        }

        $days = (int)$start->diff($end)->days + 1;
        $prevEnd = $start->modify('-1 day');
        $prevStart = $prevEnd->modify('-' . ($days - 1) . ' days');

        return [
            'preset' => $custom && $preset === RangePreset::CUSTOM ? RangePreset::CUSTOM : $preset,
            'from' => $start->format('Y-m-d'),
            'to' => $end->format('Y-m-d'),
            'days' => $days,
            'prev_from' => $prevStart->format('Y-m-d'),
            'prev_to' => $prevEnd->format('Y-m-d'),
            'label' => $label,
        ];
    }

    /**
     * Every day of a period (Y-m-d).
     *
     * @param string $from
     * @param string $to
     * @return string[]
     */
    public function days(string $from, string $to): array
    {
        $days = [];
        $day = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);
        while ($day <= $end && count($days) <= self::MAX_DAYS) {
            $days[] = $day->format('Y-m-d');
            $day = $day->modify('+1 day');
        }

        return $days;
    }

    /**
     * Strict Y-m-d or null.
     *
     * @param string|null $value
     * @return DateTimeImmutable|null
     */
    private function parse(?string $value): ?DateTimeImmutable
    {
        $value = trim((string)$value);
        $date = $value === '' ? false : DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
