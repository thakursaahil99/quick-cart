<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label as LabelResource;

/**
 * Product label rule.
 *
 * Saving or deleting a label cleans the product cache tag: labels appear on every
 * product card, so all pages showing products (and the cached carousels) refresh.
 */
class Label extends AbstractModel implements LabelInterface, IdentityInterface
{
    public const CACHE_TAG = 'qc_label';

    /**
     * @var string
     */
    protected $_eventPrefix = 'quickcart_product_label';

    /**
     * @var string[]
     */
    protected $_cacheTag = [self::CACHE_TAG, Product::CACHE_TAG];

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(LabelResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG, Product::CACHE_TAG];
    }

    /**
     * @inheritdoc
     */
    public function getLabelId(): ?int
    {
        $id = $this->getData(self::LABEL_ID);

        return $id === null ? null : (int)$id;
    }

    /**
     * @inheritdoc
     */
    public function setLabelId(int $labelId): LabelInterface
    {
        return $this->setData(self::LABEL_ID, $labelId);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): LabelInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getText(): string
    {
        return (string)$this->getData(self::TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setText(string $text): LabelInterface
    {
        return $this->setData(self::TEXT, $text);
    }

    /**
     * @inheritdoc
     */
    public function getConditionType(): string
    {
        return (string)$this->getData(self::CONDITION_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setConditionType(string $type): LabelInterface
    {
        return $this->setData(self::CONDITION_TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getConditionValue(): ?string
    {
        $value = $this->getData(self::CONDITION_VALUE);

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function setConditionValue(?string $value): LabelInterface
    {
        return $this->setData(self::CONDITION_VALUE, $value);
    }

    /**
     * @inheritdoc
     */
    public function getBackgroundColor(): string
    {
        return (string)$this->getData(self::BACKGROUND_COLOR);
    }

    /**
     * @inheritdoc
     */
    public function setBackgroundColor(string $color): LabelInterface
    {
        return $this->setData(self::BACKGROUND_COLOR, $color);
    }

    /**
     * @inheritdoc
     */
    public function getTextColor(): string
    {
        return (string)$this->getData(self::TEXT_COLOR);
    }

    /**
     * @inheritdoc
     */
    public function setTextColor(string $color): LabelInterface
    {
        return $this->setData(self::TEXT_COLOR, $color);
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setSortOrder(int $sortOrder): LabelInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritdoc
     */
    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive(bool $isActive): LabelInterface
    {
        return $this->setData(self::IS_ACTIVE, (int)$isActive);
    }

    /**
     * @inheritdoc
     */
    public function getFromDate(): ?string
    {
        $date = $this->getData(self::FROM_DATE);

        return $date ? substr((string)$date, 0, 10) : null;
    }

    /**
     * @inheritdoc
     */
    public function setFromDate(?string $date): LabelInterface
    {
        return $this->setData(self::FROM_DATE, $date ?: null);
    }

    /**
     * @inheritdoc
     */
    public function getToDate(): ?string
    {
        $date = $this->getData(self::TO_DATE);

        return $date ? substr((string)$date, 0, 10) : null;
    }

    /**
     * @inheritdoc
     */
    public function setToDate(?string $date): LabelInterface
    {
        return $this->setData(self::TO_DATE, $date ?: null);
    }

    /**
     * @inheritdoc
     */
    public function getStoreIds(): array
    {
        $storeIds = $this->getData(self::STORE_IDS);
        if (is_string($storeIds)) {
            $storeIds = explode(',', $storeIds);
        }

        return array_values(array_unique(array_map('intval', (array)$storeIds)));
    }

    /**
     * @inheritdoc
     */
    public function setStoreIds(array $storeIds): LabelInterface
    {
        return $this->setData(self::STORE_IDS, array_values(array_unique(array_map('intval', $storeIds))));
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);

        return $value === null ? null : (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);

        return $value === null ? null : (string)$value;
    }
}
