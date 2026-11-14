<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use DateTimeImmutable;
use Magento\Framework\Exception\InputException;

/**
 * Validates report date ranges (Y-m-d days in the store timezone).
 */
class DateRange
{
    public const DEFAULT_DAYS = 30;
    public const MAX_DAYS = 366;

    /**
     * Normalised [from, to]; defaults to the last 30 days ending today.
     *
     * @param string|null $from
     * @param string|null $to
     * @param DateTimeImmutable $today
     * @return array{0: string, 1: string}
     * @throws InputException
     */
    public function resolve(?string $from, ?string $to, DateTimeImmutable $today): array
    {
        $toDate = $to !== null && trim($to) !== '' ? $this->parse($to, 'to') : $today;
        $fromDate = $from !== null && trim($from) !== ''
            ? $this->parse($from, 'from')
            : $toDate->modify('-' . (self::DEFAULT_DAYS - 1) . ' days');

        if ($fromDate > $toDate) {
            throw new InputException(__('"from" must be on or before "to".'));
        }
        if ((int)$fromDate->diff($toDate)->days + 1 > self::MAX_DAYS) {
            throw new InputException(__('The date range is limited to %1 days.', self::MAX_DAYS));
        }

        return [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')];
    }

    /**
     * Strict Y-m-d parsing.
     *
     * @param string $value
     * @param string $field
     * @return DateTimeImmutable
     * @throws InputException
     */
    private function parse(string $value, string $field): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
        if (!$date || $date->format('Y-m-d') !== trim($value)) {
            throw new InputException(__('"%1" must be a date in YYYY-MM-DD format.', $field));
        }

        return $date;
    }
}
