<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Date range presets of the dashboard filter.
 */
class RangePreset implements OptionSourceInterface
{
    public const TODAY = 'today';
    public const LAST_7 = '7d';
    public const LAST_30 = '30d';
    public const LAST_90 = '90d';
    public const MONTH_TO_DATE = 'mtd';
    public const CUSTOM = 'custom';

    /**
     * Preset => label (custom excluded).
     *
     * @return array<string, string>
     */
    public function getPresets(): array
    {
        return [
            self::TODAY => (string)__('Today'),
            self::LAST_7 => (string)__('Last 7 days'),
            self::LAST_30 => (string)__('Last 30 days'),
            self::LAST_90 => (string)__('Last 90 days'),
            self::MONTH_TO_DATE => (string)__('Month to date'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getPresets() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
