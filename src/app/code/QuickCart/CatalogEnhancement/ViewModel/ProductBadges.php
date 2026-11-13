<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use QuickCart\CatalogEnhancement\Model\Attributes;

/**
 * Product badges (sale %, new, trending) and brand label for product cards.
 *
 * All values derive from real catalog data: the Magento price model (special price,
 * catalog rules, tier prices), the "Set Product as New" dates and the Trending flag.
 */
class ProductBadges implements ArgumentInterface
{
    public const BADGE_SALE = 'sale';
    public const BADGE_NEW = 'new';
    public const BADGE_TRENDING = 'trending';

    /**
     * @param TimezoneInterface $timezone
     * @param int $maxBadges
     */
    public function __construct(
        private readonly TimezoneInterface $timezone,
        private readonly int $maxBadges = 2
    ) {
    }

    /**
     * Badges for a product, most important first.
     *
     * @param Product $product
     * @return array<int, array{code: string, label: string}>
     */
    public function getBadges(Product $product): array
    {
        $badges = [];
        $discount = $this->getDiscountPercent($product);
        if ($discount > 0) {
            $badges[] = ['code' => self::BADGE_SALE, 'label' => (string)__('-%1%', $discount)];
        }
        if ($this->isNew($product)) {
            $badges[] = ['code' => self::BADGE_NEW, 'label' => (string)__('New')];
        }
        if ((int)$product->getData(Attributes::IS_TRENDING) === 1) {
            $badges[] = ['code' => self::BADGE_TRENDING, 'label' => (string)__('Trending')];
        }

        return array_slice($badges, 0, max(0, $this->maxBadges));
    }

    /**
     * Whole-percent discount of the final price versus the regular price.
     *
     * For configurable products both prices are the minimum across children.
     *
     * @param Product $product
     * @return int
     */
    public function getDiscountPercent(Product $product): int
    {
        $priceInfo = $product->getPriceInfo();
        $regular = (float)$priceInfo->getPrice(RegularPrice::PRICE_CODE)->getAmount()->getValue();
        $final = (float)$priceInfo->getPrice(FinalPrice::PRICE_CODE)->getAmount()->getValue();
        if ($regular <= 0.0 || $final >= $regular) {
            return 0;
        }

        return (int)floor(($regular - $final) / $regular * 100);
    }

    /**
     * Whether today (store timezone) falls inside the product's "new" date range.
     *
     * @param Product $product
     * @return bool
     */
    public function isNew(Product $product): bool
    {
        $from = (string)$product->getData('news_from_date');
        $to = (string)$product->getData('news_to_date');
        if ($from === '' && $to === '') {
            return false;
        }
        $today = $this->timezone->date()->format('Y-m-d');

        return ($from === '' || substr($from, 0, 10) <= $today)
            && ($to === '' || substr($to, 0, 10) >= $today);
    }

    /**
     * Brand label (option text of qc_brand), or empty string.
     *
     * @param Product $product
     * @return string
     */
    public function getBrand(Product $product): string
    {
        $value = $product->getData(Attributes::BRAND);
        if ($value === null || $value === '') {
            return '';
        }
        $attribute = $product->getResource()->getAttribute(Attributes::BRAND);
        $source = $attribute ? $attribute->getSource() : null;
        $label = $source instanceof SourceInterface ? $source->getOptionText($value) : '';

        return is_string($label) ? $label : '';
    }
}
