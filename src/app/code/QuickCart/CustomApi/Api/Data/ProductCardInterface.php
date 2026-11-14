<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Product as shown on a storefront card.
 *
 * @api
 */
interface ProductCardInterface
{
    /**
     * Product ID.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * SKU.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Product type (simple, configurable, ...).
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Storefront URL.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Main image URL, or null.
     *
     * @return string|null
     */
    public function getImageUrl(): ?string;

    /**
     * Brand name, or null.
     *
     * @return string|null
     */
    public function getBrand(): ?string;

    /**
     * Regular price (minimum for configurable products).
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Final price after special prices and catalog rules.
     *
     * @return float
     */
    public function getFinalPrice(): float;

    /**
     * Whole-percent discount, 0 when not discounted.
     *
     * @return int
     */
    public function getDiscountPercent(): int;

    /**
     * Currency code of the prices.
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Average rating 0-100, or null without reviews.
     *
     * @return int|null
     */
    public function getRatingPercent(): ?int;

    /**
     * Approved review count.
     *
     * @return int
     */
    public function getReviewCount(): int;

    /**
     * Whether the product can be bought.
     *
     * @return bool
     */
    public function getIsInStock(): bool;

    /**
     * Labels in priority order.
     *
     * @return \QuickCart\CustomApi\Api\Data\ProductLabelInterface[]
     */
    public function getLabels(): array;
}
