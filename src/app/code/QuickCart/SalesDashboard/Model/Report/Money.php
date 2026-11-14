<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\Report;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Money and number formatting for the dashboard: full amounts in the base currency and
 * compact figures for tiles and axes (Indian lakh / crore for INR, K / M otherwise).
 */
class Money
{
    /**
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * Full amount without decimals in the admin locale's format, e.g. ₹1,147,606.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function format(float $amount, string $currency): string
    {
        return $this->priceCurrency->format($amount, false, 0, null, $currency);
    }

    /**
     * Compact amount: ₹11.5 L, ₹1.2 Cr, ₹950 / $12.9K, $4.2M.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function compact(float $amount, string $currency): string
    {
        return $this->symbol($currency) . $this->compactNumber($amount, $currency === 'INR');
    }

    /**
     * Compact number without currency.
     *
     * @param float $value
     * @param bool $indian Lakh / crore instead of K / M
     * @return string
     */
    public function compactNumber(float $value, bool $indian = false): string
    {
        $abs = abs($value);
        $units = $indian
            ? [[1e7, ' Cr'], [1e5, ' L'], [1e3, 'K']]
            : [[1e9, 'B'], [1e6, 'M'], [1e3, 'K']];
        foreach ($units as [$size, $suffix]) {
            if ($abs >= $size) {
                $scaled = $value / $size;
                $decimals = abs($scaled) >= 100 ? 0 : (abs($scaled) >= 10 ? 1 : 2);
                $number = number_format($scaled, $decimals, '.', '');
                if ($decimals > 0) {
                    $number = rtrim(rtrim($number, '0'), '.');
                }

                return $number . $suffix;
            }
        }

        return number_format($value, 0, '.', ',');
    }

    /**
     * Signed percent change from $previous to $current; null when there is no base.
     *
     * @param float $current
     * @param float $previous
     * @return float|null
     */
    public function change(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.00001) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }

    /**
     * Currency symbol.
     *
     * @param string $currency
     * @return string
     */
    public function symbol(string $currency): string
    {
        return (string)$this->priceCurrency->getCurrencySymbol(null, $currency);
    }
}
