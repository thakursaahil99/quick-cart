<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Category in the storefront tree.
 *
 * @api
 */
interface CategoryNodeInterface
{
    /**
     * Category ID.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Parent category ID.
     *
     * @return int
     */
    public function getParentId(): int;

    /**
     * Name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * URL key.
     *
     * @return string|null
     */
    public function getUrlKey(): ?string;

    /**
     * Storefront URL.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Category image URL, or null.
     *
     * @return string|null
     */
    public function getImageUrl(): ?string;

    /**
     * Depth below the store root (1 = top level).
     *
     * @return int
     */
    public function getLevel(): int;

    /**
     * Sort position among siblings.
     *
     * @return int
     */
    public function getPosition(): int;

    /**
     * Visible products, including those of sub-categories for anchor categories.
     *
     * @return int
     */
    public function getProductCount(): int;

    /**
     * Sub-categories.
     *
     * @return \QuickCart\CustomApi\Api\Data\CategoryNodeInterface[]
     */
    public function getChildren(): array;
}
