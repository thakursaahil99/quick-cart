<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Api\Data;

/**
 * Product label rule.
 *
 * @api
 */
interface LabelInterface
{
    public const LABEL_ID = 'label_id';
    public const NAME = 'name';
    public const TEXT = 'text';
    public const CONDITION_TYPE = 'condition_type';
    public const CONDITION_VALUE = 'condition_value';
    public const BACKGROUND_COLOR = 'background_color';
    public const TEXT_COLOR = 'text_color';
    public const SORT_ORDER = 'sort_order';
    public const IS_ACTIVE = 'is_active';
    public const FROM_DATE = 'from_date';
    public const TO_DATE = 'to_date';
    public const STORE_IDS = 'store_ids';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Label ID.
     *
     * @return int|null
     */
    public function getLabelId(): ?int;

    /**
     * Set label ID.
     *
     * @param int $labelId
     * @return $this
     */
    public function setLabelId(int $labelId): self;

    /**
     * Admin name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set admin name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Storefront text; "{discount}" is replaced with the product's discount percent.
     *
     * @return string
     */
    public function getText(): string;

    /**
     * Set storefront text.
     *
     * @param string $text
     * @return $this
     */
    public function setText(string $text): self;

    /**
     * Condition type (sku, category, sale, new, trending).
     *
     * @return string
     */
    public function getConditionType(): string;

    /**
     * Set condition type.
     *
     * @param string $type
     * @return $this
     */
    public function setConditionType(string $type): self;

    /**
     * Condition value: SKU list, category ID list or minimum discount percent.
     *
     * @return string|null
     */
    public function getConditionValue(): ?string;

    /**
     * Set condition value.
     *
     * @param string|null $value
     * @return $this
     */
    public function setConditionValue(?string $value): self;

    /**
     * Background colour (#RRGGBB).
     *
     * @return string
     */
    public function getBackgroundColor(): string;

    /**
     * Set background colour.
     *
     * @param string $color
     * @return $this
     */
    public function setBackgroundColor(string $color): self;

    /**
     * Text colour (#RRGGBB).
     *
     * @return string
     */
    public function getTextColor(): string;

    /**
     * Set text colour.
     *
     * @param string $color
     * @return $this
     */
    public function setTextColor(string $color): self;

    /**
     * Priority; lower values are shown first.
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Set priority.
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self;

    /**
     * Whether the rule is enabled.
     *
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * Enable or disable the rule.
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self;

    /**
     * First active day (Y-m-d) or null.
     *
     * @return string|null
     */
    public function getFromDate(): ?string;

    /**
     * Set first active day.
     *
     * @param string|null $date
     * @return $this
     */
    public function setFromDate(?string $date): self;

    /**
     * Last active day (Y-m-d) or null.
     *
     * @return string|null
     */
    public function getToDate(): ?string;

    /**
     * Set last active day.
     *
     * @param string|null $date
     * @return $this
     */
    public function setToDate(?string $date): self;

    /**
     * Store view IDs (0 = all store views).
     *
     * @return int[]
     */
    public function getStoreIds(): array;

    /**
     * Set store view IDs.
     *
     * @param int[] $storeIds
     * @return $this
     */
    public function setStoreIds(array $storeIds): self;

    /**
     * Creation time.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Last update time.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
