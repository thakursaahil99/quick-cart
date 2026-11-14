<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\Label;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Which products a label rule applies to.
 */
class ConditionType implements OptionSourceInterface
{
    /** Listed SKUs (condition value: comma or line separated SKUs). */
    public const SKU = 'sku';

    /** Products in the listed categories or their sub-categories (condition value: category IDs). */
    public const CATEGORY = 'category';

    /** Discounted products (condition value: minimum discount percent, default 1). */
    public const SALE = 'sale';

    /** Products inside their "Set Product as New" date range. */
    public const NEW = 'new';

    /** Products flagged "Trending" (qc_is_trending). */
    public const TRENDING = 'trending';

    /**
     * Types whose condition value is required / meaningful.
     */
    public const TYPES_WITH_VALUE = [self::SKU, self::CATEGORY, self::SALE];

    /**
     * Type => label.
     *
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return [
            self::SALE => (string)__('On sale (minimum discount %)'),
            self::NEW => (string)__('New products (news from/to dates)'),
            self::TRENDING => (string)__('Trending flag'),
            self::SKU => (string)__('Specific SKUs'),
            self::CATEGORY => (string)__('Categories (incl. sub-categories)'),
        ];
    }

    /**
     * Whether the type is known.
     *
     * @param string $type
     * @return bool
     */
    public function isValid(string $type): bool
    {
        return array_key_exists($type, $this->getOptions());
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
