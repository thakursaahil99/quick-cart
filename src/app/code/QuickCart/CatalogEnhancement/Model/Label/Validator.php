<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\Label;

use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;

/**
 * Normalises and validates a label rule before it is saved.
 */
class Validator
{
    public const NAME_MAX_LENGTH = 120;
    public const TEXT_MAX_LENGTH = 40;
    public const SKU_MAX_LENGTH = 64;
    public const SORT_ORDER_LIMIT = 9999;
    private const HEX_COLOR = '/^#[0-9A-F]{6}$/';
    private const DATE = '/^\d{4}-\d{2}-\d{2}$/';

    /**
     * @param ConditionType $conditionType
     */
    public function __construct(
        private readonly ConditionType $conditionType
    ) {
    }

    /**
     * Trim text, upper-case colours and canonicalise the condition value.
     *
     * @param LabelInterface $label
     * @return void
     */
    public function normalize(LabelInterface $label): void
    {
        $label->setName(trim($label->getName()));
        $label->setText(trim($label->getText()));
        $label->setBackgroundColor(strtoupper(trim($label->getBackgroundColor())));
        $label->setTextColor(strtoupper(trim($label->getTextColor())));
        $label->setFromDate($this->normalizeDate($label->getFromDate()));
        $label->setToDate($this->normalizeDate($label->getToDate()));

        $value = (string)$label->getConditionValue();
        switch ($label->getConditionType()) {
            case ConditionType::SKU:
                $label->setConditionValue(implode(', ', $this->splitList($value)));
                break;
            case ConditionType::CATEGORY:
                $label->setConditionValue(implode(',', $this->splitList($value)));
                break;
            case ConditionType::SALE:
                $label->setConditionValue(trim($value) === '' ? '1' : trim($value));
                break;
            default:
                $label->setConditionValue(null);
        }
    }

    /**
     * Validation errors (empty when valid).
     *
     * @param LabelInterface $label
     * @return string[]
     */
    public function validate(LabelInterface $label): array
    {
        $errors = [];
        $name = $label->getName();
        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            $errors[] = (string)__('Name is required (max %1 characters).', self::NAME_MAX_LENGTH);
        }
        $text = $label->getText();
        if ($text === '' || mb_strlen($text) > self::TEXT_MAX_LENGTH) {
            $errors[] = (string)__('Label text is required (max %1 characters).', self::TEXT_MAX_LENGTH);
        }
        foreach (['Background colour' => $label->getBackgroundColor(), 'Text colour' => $label->getTextColor()]
                 as $field => $color) {
            if (!preg_match(self::HEX_COLOR, $color)) {
                $errors[] = (string)__('%1 must be a hex colour like #2563EB.', __($field));
            }
        }
        if (abs($label->getSortOrder()) > self::SORT_ORDER_LIMIT) {
            $errors[] = (string)__('Priority must be between -%1 and %1.', self::SORT_ORDER_LIMIT);
        }
        $errors = array_merge($errors, $this->validateCondition($label), $this->validateDates($label));

        return $errors;
    }

    /**
     * Split a comma / semicolon / line separated list into unique trimmed values.
     *
     * @param string $value
     * @return string[]
     */
    public function splitList(string $value): array
    {
        $parts = preg_split('/[\s,;]*[,;\r\n]+[\s,;]*/', trim($value)) ?: [];
        $parts = array_filter(array_map('trim', $parts), static fn (string $part) => $part !== '');

        return array_values(array_unique($parts));
    }

    /**
     * Condition type / value errors.
     *
     * @param LabelInterface $label
     * @return string[]
     */
    private function validateCondition(LabelInterface $label): array
    {
        $type = $label->getConditionType();
        if (!$this->conditionType->isValid($type)) {
            return [(string)__('Choose which products the label applies to.')];
        }
        $value = (string)$label->getConditionValue();
        if ($type === ConditionType::SKU) {
            $skus = $this->splitList($value);
            if (!$skus) {
                return [(string)__('Enter at least one SKU.')];
            }
            foreach ($skus as $sku) {
                if (mb_strlen($sku) > self::SKU_MAX_LENGTH) {
                    return [(string)__('SKU "%1" is longer than %2 characters.', $sku, self::SKU_MAX_LENGTH)];
                }
            }
        }
        if ($type === ConditionType::CATEGORY) {
            $ids = $this->splitList($value);
            if (!$ids || array_filter($ids, static fn (string $id) => !ctype_digit($id) || (int)$id < 1)) {
                return [(string)__('Enter category IDs as positive numbers separated by commas.')];
            }
        }
        if ($type === ConditionType::SALE
            && (!ctype_digit($value) || (int)$value < 1 || (int)$value > 99)) {
            return [(string)__('Minimum discount must be a whole percent between 1 and 99.')];
        }

        return [];
    }

    /**
     * Date format / range errors.
     *
     * @param LabelInterface $label
     * @return string[]
     */
    private function validateDates(LabelInterface $label): array
    {
        $from = $label->getFromDate();
        $to = $label->getToDate();
        foreach ([$from, $to] as $date) {
            if ($date !== null && !$this->isValidDate($date)) {
                return [(string)__('Dates must be valid calendar days (YYYY-MM-DD).')];
            }
        }
        if ($from !== null && $to !== null && $from > $to) {
            return [(string)__('"Active to" must be on or after "Active from".')];
        }

        return [];
    }

    /**
     * Y-m-d check including the calendar.
     *
     * @param string $date
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        if (!preg_match(self::DATE, $date)) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year);
    }

    /**
     * Reduce "2026-11-13 00:00:00" (and empty strings) to Y-m-d / null.
     *
     * @param string|null $date
     * @return string|null
     */
    private function normalizeDate(?string $date): ?string
    {
        $date = trim((string)$date);

        return $date === '' ? null : substr($date, 0, 10);
    }
}
