<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;
use QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation as RecommendationResource;

/**
 * Recommendation row. Saving/deleting refreshes the viewed product's cached pages.
 */
class Recommendation extends AbstractModel implements RecommendationInterface, IdentityInterface
{
    public const CACHE_TAG = 'qc_reco';

    /**
     * @var string
     */
    protected $_eventPrefix = 'quickcart_product_recommendation';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(RecommendationResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG, Product::CACHE_TAG . '_' . $this->getProductId()];
    }

    /**
     * @inheritdoc
     */
    public function getRecommendationId(): ?int
    {
        $id = $this->getData(self::RECOMMENDATION_ID);

        return $id === null ? null : (int)$id;
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProductId(int $productId): RecommendationInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getRecommendedProductId(): int
    {
        return (int)$this->getData(self::RECOMMENDED_PRODUCT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRecommendedProductId(int $productId): RecommendationInterface
    {
        return $this->setData(self::RECOMMENDED_PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setType(string $type): RecommendationInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getSource(): string
    {
        return (string)$this->getData(self::SOURCE);
    }

    /**
     * @inheritdoc
     */
    public function setSource(string $source): RecommendationInterface
    {
        return $this->setData(self::SOURCE, $source);
    }

    /**
     * @inheritdoc
     */
    public function getScore(): float
    {
        return (float)$this->getData(self::SCORE);
    }

    /**
     * @inheritdoc
     */
    public function setScore(float $score): RecommendationInterface
    {
        return $this->setData(self::SCORE, $score);
    }

    /**
     * @inheritdoc
     */
    public function getPosition(): int
    {
        return (int)$this->getData(self::POSITION);
    }

    /**
     * @inheritdoc
     */
    public function setPosition(int $position): RecommendationInterface
    {
        return $this->setData(self::POSITION, $position);
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
    public function setIsActive(bool $isActive): RecommendationInterface
    {
        return $this->setData(self::IS_ACTIVE, (int)$isActive);
    }
}
