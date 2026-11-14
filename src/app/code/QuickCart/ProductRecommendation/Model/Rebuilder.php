<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Indexer\CacheContextFactory;
use Psr\Log\LoggerInterface;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;
use QuickCart\ProductRecommendation\Model\Builder\BoughtTogetherBuilder;
use QuickCart\ProductRecommendation\Model\Builder\SimilarProductsBuilder;
use QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation as RecommendationResource;

/**
 * Recomputes the automatic recommendations (cron, CLI and the admin "Rebuild" button).
 */
class Rebuilder
{
    /**
     * Pairs kept per product in the table (the storefront shows fewer).
     */
    private const MAX_STORED_PER_PRODUCT = 8;

    /**
     * @param BoughtTogetherBuilder $boughtTogetherBuilder
     * @param SimilarProductsBuilder $similarProductsBuilder
     * @param RecommendationResource $resource
     * @param Config $config
     * @param CacheContextFactory $cacheContextFactory
     * @param EventManager $eventManager
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly BoughtTogetherBuilder $boughtTogetherBuilder,
        private readonly SimilarProductsBuilder $similarProductsBuilder,
        private readonly RecommendationResource $resource,
        private readonly Config $config,
        private readonly CacheContextFactory $cacheContextFactory,
        private readonly EventManager $eventManager,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Rebuild both automatic sources.
     *
     * @return array{bought_together: int, similar: int} Rows written per type
     * @throws \Exception
     */
    public function rebuild(): array
    {
        $boughtTogether = $this->boughtTogetherBuilder->build(
            $this->config->getLookbackDays(),
            $this->config->getMinOrders(),
            self::MAX_STORED_PER_PRODUCT
        );
        $similar = $this->similarProductsBuilder->build(self::MAX_STORED_PER_PRODUCT);

        $result = [
            'bought_together' => $this->resource->replaceAutomatic(
                RecommendationInterface::TYPE_BOUGHT_TOGETHER,
                RecommendationInterface::SOURCE_ORDERS,
                $boughtTogether
            ),
            'similar' => $this->resource->replaceAutomatic(
                RecommendationInterface::TYPE_SIMILAR,
                RecommendationInterface::SOURCE_CATALOG,
                $similar
            ),
        ];
        $this->cleanCache();
        $this->logger->info('QuickCart recommendations rebuilt', $result);

        return $result;
    }

    /**
     * Refresh block and full-page caches that show recommendations.
     *
     * @return void
     */
    private function cleanCache(): void
    {
        $cacheContext = $this->cacheContextFactory->create();
        $cacheContext->registerTags([Recommendation::CACHE_TAG]);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $cacheContext]);
        $this->cache->clean([Recommendation::CACHE_TAG]);
    }
}
