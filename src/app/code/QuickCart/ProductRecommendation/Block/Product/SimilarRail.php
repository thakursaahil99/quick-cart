<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Block\Product;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Review\Model\ResourceModel\Review\Summary as ReviewSummary;
use QuickCart\CatalogEnhancement\Model\ProductSource\SourcePool;
use QuickCart\CatalogEnhancement\ViewModel\ProductBadges;
use QuickCart\ProductRecommendation\Block\ProductRail;
use QuickCart\ProductRecommendation\Model\Config;
use QuickCart\ProductRecommendation\Model\RecommendationProvider;

/**
 * "More like this" rail on the product page (catalog similarity + manual rows),
 * skipping products already shown as related / up-sell links.
 */
class SimilarRail extends ProductRail
{
    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Visibility $productVisibility
     * @param SourcePool $sourcePool
     * @param ProductBadges $badges
     * @param HttpContext $httpContext
     * @param UrlHelper $urlHelper
     * @param ReviewSummary $reviewSummary
     * @param RecommendationProvider $recommendationProvider
     * @param Config $config
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Visibility $productVisibility,
        SourcePool $sourcePool,
        ProductBadges $badges,
        HttpContext $httpContext,
        UrlHelper $urlHelper,
        ReviewSummary $reviewSummary,
        private readonly RecommendationProvider $recommendationProvider,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $collectionFactory,
            $productVisibility,
            $sourcePool,
            $badges,
            $httpContext,
            $urlHelper,
            $reviewSummary,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function getProductIds(): array
    {
        $product = $this->getProduct();

        return $product ? $this->recommendationProvider->getSimilarIds($product, $this->getProductsCount()) : [];
    }

    /**
     * @inheritdoc
     */
    public function getProductsCount(): int
    {
        return $this->config->getSimilarLimit();
    }

    /**
     * @inheritdoc
     */
    public function getSourceCode(): string
    {
        return 'similar';
    }
}
