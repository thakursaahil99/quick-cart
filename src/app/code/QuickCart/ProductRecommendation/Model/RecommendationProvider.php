<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model;

use Magento\Catalog\Model\Product;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;
use QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation as RecommendationResource;

/**
 * Candidate product IDs for a viewed product, best first (shared by the storefront
 * blocks and the QuickCart REST API). Callers still apply store, status, visibility
 * and stock filters when loading the products.
 */
class RecommendationProvider
{
    private const CANDIDATE_FACTOR = 4;

    /**
     * @param RecommendationResource $resource
     * @param Config $config
     */
    public function __construct(
        private readonly RecommendationResource $resource,
        private readonly Config $config
    ) {
    }

    /**
     * "Frequently bought together" candidates: co-purchases and manual rows, topped up
     * with cross-sells while order history is thin (configurable).
     *
     * @param Product $product
     * @param int $limit Products the caller wants to show
     * @return int[]
     */
    public function getBoughtTogetherIds(Product $product, int $limit): array
    {
        $productId = (int)$product->getId();
        if (!$productId || !$this->config->isBoughtTogetherEnabled()) {
            return [];
        }
        $ids = $this->resource->getRecommendedIds(
            $productId,
            RecommendationInterface::TYPE_BOUGHT_TOGETHER,
            $limit * self::CANDIDATE_FACTOR
        );
        if (count($ids) < $limit * self::CANDIDATE_FACTOR && $this->config->isCrossSellFallbackEnabled()) {
            $ids = array_merge($ids, array_map('intval', $product->getCrossSellProductIds()));
        }

        return array_values(array_diff(array_unique($ids), [$productId]));
    }

    /**
     * "More like this" candidates, skipping products already linked as related / up-sell.
     *
     * @param Product $product
     * @param int $limit
     * @return int[]
     */
    public function getSimilarIds(Product $product, int $limit): array
    {
        $productId = (int)$product->getId();
        if (!$productId || !$this->config->isSimilarEnabled()) {
            return [];
        }
        $shown = array_merge(
            [$productId],
            array_map('intval', $product->getRelatedProductIds()),
            array_map('intval', $product->getUpSellProductIds())
        );
        $ids = $this->resource->getRecommendedIds(
            $productId,
            RecommendationInterface::TYPE_SIMILAR,
            $limit + count($shown)
        );

        return array_slice(array_values(array_diff($ids, $shown)), 0, $limit);
    }
}
