<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Test\Unit\Model\Label;

use Magento\Catalog\Model\Product;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;
use QuickCart\CatalogEnhancement\Model\Label\ConditionType;
use QuickCart\CatalogEnhancement\Model\Label\Matcher;
use QuickCart\CatalogEnhancement\Model\Label\ProductFacts;
use QuickCart\CatalogEnhancement\Model\Label\Resolver;
use QuickCart\CatalogEnhancement\Model\Label\Validator;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label\Collection;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label\CollectionFactory;

#[CoversClass(Resolver::class)]
#[CoversClass(Matcher::class)]
class ResolverTest extends TestCase
{
    public function testLabelsAreMatchedInPriorityOrderAndCapped(): void
    {
        $labels = [
            $this->label(1, ConditionType::SALE, '10', '-{discount}%', '#F59E0B'),
            $this->label(2, ConditionType::SKU, 'qc-x, QC-PH-EDGE5', "Editor's pick", '#059669'),
            $this->label(3, ConditionType::NEW, null, 'New', '#2563EB'),
        ];
        $facts = $this->facts(discount: 12, isNew: true);
        $resolver = $this->resolver($labels, $facts);
        $product = $this->product('qc-ph-edge5');

        $result = $resolver->resolve($product, 2);

        $this->assertSame(['-12%', "Editor's pick"], array_column($result, 'label'));
        $this->assertSame(['sale', 'custom'], array_column($result, 'code'));
        $this->assertSame('background-color:#F59E0B;color:#FFFFFF', $result[0]['style']);
    }

    public function testDiscountBelowMinimumAndDiscountTokenWithoutDiscountAreSkipped(): void
    {
        $labels = [
            $this->label(1, ConditionType::SALE, '20', 'Big sale', '#F59E0B'),
            $this->label(2, ConditionType::TRENDING, null, 'Save {discount}%', '#111827'),
            $this->label(3, ConditionType::TRENDING, null, 'Trending', '#111827'),
        ];
        $resolver = $this->resolver($labels, $this->facts(discount: 0, trending: true));

        $this->assertSame(['Trending'], array_column($resolver->resolve($this->product('A'), 3), 'label'));
    }

    public function testCategoryLabelsMatchSubCategories(): void
    {
        $facts = $this->facts();
        $facts->method('expandCategoryIds')->willReturn([19, 20, 21]);
        $facts->method('getCategoryIds')->willReturn([4, 21]);
        $resolver = $this->resolver([$this->label(1, ConditionType::CATEGORY, '19', 'Gaming Week', '#7C3AED')], $facts);

        $this->assertSame(['Gaming Week'], array_column($resolver->resolve($this->product('A'), 2), 'label'));
        $this->assertTrue($resolver->needsCategoryData());
    }

    public function testInvalidStoredColoursAreIgnored(): void
    {
        $resolver = $this->resolver(
            [$this->label(1, ConditionType::NEW, null, 'New', 'red;top:0')],
            $this->facts(isNew: true)
        );

        $this->assertSame([], $resolver->resolve($this->product('A'), 2));
    }

    /**
     * @param LabelInterface[] $labels
     * @param ProductFacts $facts
     * @return Resolver
     */
    private function resolver(array $labels, ProductFacts $facts): Resolver
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addStoreFilter')->willReturnSelf();
        $collection->method('addActiveFilter')->willReturnSelf();
        $collection->method('addPriorityOrder')->willReturnSelf();
        $collection->method('getItems')->willReturn($labels);
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Resolver($factory, new Matcher($facts, new Validator(new ConditionType())), $facts, $storeManager);
    }

    /**
     * @param int $discount
     * @param bool $isNew
     * @param bool $trending
     * @return ProductFacts&\PHPUnit\Framework\MockObject\Stub
     */
    private function facts(int $discount = 0, bool $isNew = false, bool $trending = false): ProductFacts
    {
        $facts = $this->createStub(ProductFacts::class);
        $facts->method('getToday')->willReturn('2026-11-13');
        $facts->method('getDiscountPercent')->willReturn($discount);
        $facts->method('isNew')->willReturn($isNew);
        $facts->method('isTrending')->willReturn($trending);

        return $facts;
    }

    private function label(int $id, string $type, ?string $value, string $text, string $background): LabelInterface
    {
        $label = $this->createStub(LabelInterface::class);
        $label->method('getLabelId')->willReturn($id);
        $label->method('getConditionType')->willReturn($type);
        $label->method('getConditionValue')->willReturn($value);
        $label->method('getText')->willReturn($text);
        $label->method('getBackgroundColor')->willReturn($background);
        $label->method('getTextColor')->willReturn('#FFFFFF');

        return $label;
    }

    private function product(string $sku): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getSku')->willReturn($sku);
        $product->method('getId')->willReturn(5);

        return $product;
    }
}
