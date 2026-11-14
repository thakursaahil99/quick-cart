<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Block;

/**
 * "Recently viewed" rail rendered for the product IDs the visitor's browser sent
 * (see Controller\Recent\Render). The list itself lives in the visitor's localStorage,
 * so pages stay fully cacheable.
 */
class RecentlyViewed extends ProductRail
{
    /**
     * @var int[]
     */
    private array $productIds = [];

    /**
     * Set the products to render, most recent first.
     *
     * @param int[] $productIds
     * @return $this
     */
    public function setProductIds(array $productIds): self
    {
        $this->productIds = array_values(array_map('intval', $productIds));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProductIds(): array
    {
        return array_slice($this->productIds, 0, $this->getProductsCount());
    }

    /**
     * @inheritdoc
     */
    public function getSourceCode(): string
    {
        return 'recent';
    }
}
