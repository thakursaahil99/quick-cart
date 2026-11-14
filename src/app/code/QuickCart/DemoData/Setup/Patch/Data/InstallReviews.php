<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Review\Model\Rating;
use Magento\Review\Model\Rating\OptionFactory as RatingOptionFactory;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\ResourceModel\Rating as RatingResource;
use Magento\Review\Model\ResourceModel\Rating\Option as RatingOptionResource;
use Magento\Review\Model\ResourceModel\Rating\Option\CollectionFactory as RatingOptionCollectionFactory;
use Magento\Review\Model\ResourceModel\Review as ReviewResource;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\DemoData\Model\Fixture;

/**
 * Activates a 5-star "Rating" for the storefront and adds approved demo reviews.
 *
 * Magento has no service contract for reviews/ratings, so models are persisted
 * through their resource models (the same approach as Magento's sample data).
 */
class InstallReviews implements DataPatchInterface
{
    private const RATING_CODE = 'Rating';

    /**
     * @param Fixture $fixture
     * @param ProductRepositoryInterface $productRepository
     * @param ReviewFactory $reviewFactory
     * @param ReviewResource $reviewResource
     * @param RatingFactory $ratingFactory
     * @param RatingResource $ratingResource
     * @param RatingOptionFactory $ratingOptionFactory
     * @param RatingOptionResource $ratingOptionResource
     * @param RatingOptionCollectionFactory $ratingOptionCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param State $appState
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly Fixture $fixture,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ReviewFactory $reviewFactory,
        private readonly ReviewResource $reviewResource,
        private readonly RatingFactory $ratingFactory,
        private readonly RatingResource $ratingResource,
        private readonly RatingOptionFactory $ratingOptionFactory,
        private readonly RatingOptionResource $ratingOptionResource,
        private readonly RatingOptionCollectionFactory $ratingOptionCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $appState
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function (): void {
            $this->install();
        });

        return $this;
    }

    /**
     * Create the rating and the reviews.
     *
     * @return void
     */
    private function install(): void
    {
        $storeId = (int)$this->storeManager->getDefaultStoreView()->getId();
        $rating = $this->getOrCreateRating($storeId);
        $optionIds = $this->getOptionIdsByValue((int)$rating->getId());

        foreach ($this->fixture->getCatalog()['products'] as $data) {
            $productId = (int)$this->productRepository->get($data['sku'])->getId();
            foreach ($data['reviews'] ?? [] as $item) {
                /** @var Review $review */
                $review = $this->reviewFactory->create();
                $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
                    ->setEntityPkValue($productId)
                    ->setNickname($item['nickname'])
                    ->setTitle($item['title'])
                    ->setDetail($item['detail'])
                    ->setStatusId(Review::STATUS_APPROVED)
                    ->setStoreId($storeId)
                    ->setStores([$storeId]);
                $this->reviewResource->save($review);

                $this->ratingFactory->create()
                    ->setRatingId($rating->getId())
                    ->setReviewId($review->getId())
                    ->addOptionVote($optionIds[(int)$item['rating']], $productId);
                $review->aggregate();
            }
        }
    }

    /**
     * Load the "Rating" rating or create it with five options (1-5 stars).
     *
     * @param int $storeId
     * @return Rating
     */
    private function getOrCreateRating(int $storeId): Rating
    {
        $rating = $this->ratingFactory->create();
        $this->ratingResource->load($rating, self::RATING_CODE, 'rating_code');
        if ($rating->getId()) {
            return $rating;
        }

        $rating->setRatingCode(self::RATING_CODE)
            ->setRatingCodes([$storeId => self::RATING_CODE])
            ->setStores([Store::DEFAULT_STORE_ID, $storeId])
            ->setIsActive(1)
            ->setPosition(0)
            ->setEntityId($rating->getEntityIdByCode(Rating::ENTITY_PRODUCT_CODE));
        $this->ratingResource->save($rating);

        for ($stars = 1; $stars <= 5; $stars++) {
            $option = $this->ratingOptionFactory->create();
            $option->setCode((string)$stars)
                ->setValue($stars)
                ->setRatingId($rating->getId())
                ->setPosition($stars);
            $this->ratingOptionResource->save($option);
        }

        return $rating;
    }

    /**
     * Star value => rating option id.
     *
     * @param int $ratingId
     * @return array<int, int>
     */
    private function getOptionIdsByValue(int $ratingId): array
    {
        $map = [];
        foreach ($this->ratingOptionCollectionFactory->create()->addRatingFilter($ratingId) as $option) {
            $map[(int)$option->getValue()] = (int)$option->getId();
        }

        return $map;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [InstallProducts::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
