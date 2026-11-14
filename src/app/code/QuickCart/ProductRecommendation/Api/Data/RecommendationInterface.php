<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Api\Data;

/**
 * "Shoppers looking at product X should also see product Y".
 *
 * @api
 */
interface RecommendationInterface
{
    public const RECOMMENDATION_ID = 'recommendation_id';
    public const PRODUCT_ID = 'product_id';
    public const RECOMMENDED_PRODUCT_ID = 'recommended_product_id';
    public const TYPE = 'type';
    public const SOURCE = 'source';
    public const SCORE = 'score';
    public const POSITION = 'position';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const TYPE_BOUGHT_TOGETHER = 'bought_together';
    public const TYPE_SIMILAR = 'similar';

    public const SOURCE_ORDERS = 'orders';
    public const SOURCE_CATALOG = 'catalog';
    public const SOURCE_MANUAL = 'manual';

    /**
     * Recommendation ID.
     *
     * @return int|null
     */
    public function getRecommendationId(): ?int;

    /**
     * Viewed product ID.
     *
     * @return int
     */
    public function getProductId(): int;

    /**
     * Set viewed product ID.
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * Recommended product ID.
     *
     * @return int
     */
    public function getRecommendedProductId(): int;

    /**
     * Set recommended product ID.
     *
     * @param int $productId
     * @return $this
     */
    public function setRecommendedProductId(int $productId): self;

    /**
     * Type (bought_together or similar).
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set type.
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * Source (orders, catalog or manual).
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Set source.
     *
     * @param string $source
     * @return $this
     */
    public function setSource(string $source): self;

    /**
     * Score: co-purchase order count (orders) or similarity (catalog).
     *
     * @return float
     */
    public function getScore(): float;

    /**
     * Set score.
     *
     * @param float $score
     * @return $this
     */
    public function setScore(float $score): self;

    /**
     * Position; manual recommendations are shown first, lowest position first.
     *
     * @return int
     */
    public function getPosition(): int;

    /**
     * Set position.
     *
     * @param int $position
     * @return $this
     */
    public function setPosition(int $position): self;

    /**
     * Whether the recommendation is shown.
     *
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * Enable or disable the recommendation.
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self;
}
