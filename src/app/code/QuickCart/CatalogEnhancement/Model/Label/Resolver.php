<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\Label;

use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label\CollectionFactory;

/**
 * Resolves the storefront labels of a product from the active label rules.
 *
 * The active rules of a store are loaded once per request (one query plus one for
 * store assignments) and evaluated in priority order.
 */
class Resolver
{
    /**
     * CSS modifier per condition type (keeps the theme's per-type styling hooks).
     */
    private const CODES = [
        ConditionType::SALE => 'sale',
        ConditionType::NEW => 'new',
        ConditionType::TRENDING => 'trending',
        ConditionType::SKU => 'custom',
        ConditionType::CATEGORY => 'custom',
    ];

    private const DISCOUNT_TOKEN = '{discount}';

    /**
     * @var array<int, LabelInterface[]>
     */
    private array $activeLabels = [];

    /**
     * @param CollectionFactory $collectionFactory
     * @param Matcher $matcher
     * @param ProductFacts $facts
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly Matcher $matcher,
        private readonly ProductFacts $facts,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Labels for a product, highest priority first.
     *
     * @param Product $product
     * @param int $limit
     * @return array<int, array{id: int, code: string, label: string, background: string, color: string, style: string}>
     */
    public function resolve(Product $product, int $limit): array
    {
        $result = [];
        foreach ($this->getActiveLabels() as $label) {
            if (count($result) >= $limit) {
                break;
            }
            if (!$this->matcher->matches($label, $product)) {
                continue;
            }
            $text = $label->getText();
            if (str_contains($text, self::DISCOUNT_TOKEN)) {
                $discount = $this->facts->getDiscountPercent($product);
                if ($discount < 1) {
                    continue;
                }
                $text = str_replace(self::DISCOUNT_TOKEN, (string)$discount, $text);
            }
            $result[] = [
                'id' => (int)$label->getLabelId(),
                'code' => self::CODES[$label->getConditionType()] ?? 'custom',
                'label' => $text,
                'background' => $label->getBackgroundColor(),
                'color' => $label->getTextColor(),
                'style' => sprintf(
                    'background-color:%s;color:%s',
                    $label->getBackgroundColor(),
                    $label->getTextColor()
                ),
            ];
        }

        return $result;
    }

    /**
     * Active rules of the current store, in priority order.
     *
     * @return LabelInterface[]
     */
    public function getActiveLabels(): array
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        if (!isset($this->activeLabels[$storeId])) {
            $collection = $this->collectionFactory->create()
                ->addStoreFilter($storeId)
                ->addActiveFilter($this->facts->getToday())
                ->addPriorityOrder();
            // Colours are validated on save; re-check so a manual DB edit cannot inject CSS.
            $this->activeLabels[$storeId] = array_values(array_filter(
                $collection->getItems(),
                static fn (LabelInterface $label) => preg_match('/^#[0-9A-F]{6}$/i', $label->getBackgroundColor())
                    && preg_match('/^#[0-9A-F]{6}$/i', $label->getTextColor())
            ));
        }

        return $this->activeLabels[$storeId];
    }

    /**
     * Whether any active rule needs category data (lets callers skip the preload).
     *
     * @return bool
     */
    public function needsCategoryData(): bool
    {
        foreach ($this->getActiveLabels() as $label) {
            if ($label->getConditionType() === ConditionType::CATEGORY) {
                return true;
            }
        }

        return false;
    }
}
