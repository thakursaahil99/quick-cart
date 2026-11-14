<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Test\Unit\Model\Label;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\CatalogEnhancement\Model\Label;
use QuickCart\CatalogEnhancement\Model\Label\ConditionType;
use QuickCart\CatalogEnhancement\Model\Label\Validator;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label as LabelResource;

#[CoversClass(Validator::class)]
class ValidatorTest extends TestCase
{
    public function testValidLabelIsNormalised(): void
    {
        $label = $this->createLabel([
            'name' => '  Diwali audio  ',
            'text' => ' -{discount}% ',
            'condition_type' => ConditionType::SKU,
            'condition_value' => "qc-a ,QC-B\nqc-a;;QC-C",
            'background_color' => '#f59e0b',
            'text_color' => '#111827',
            'from_date' => '2026-11-01 00:00:00',
            'to_date' => '',
        ]);
        $validator = new Validator(new ConditionType());
        $validator->normalize($label);

        $this->assertSame([], $validator->validate($label));
        $this->assertSame('Diwali audio', $label->getName());
        $this->assertSame('-{discount}%', $label->getText());
        $this->assertSame('#F59E0B', $label->getBackgroundColor());
        $this->assertSame('qc-a, QC-B, QC-C', $label->getConditionValue());
        $this->assertSame('2026-11-01', $label->getFromDate());
        $this->assertNull($label->getToDate());
    }

    public function testValueIsDroppedForTypesWithoutOne(): void
    {
        $label = $this->createLabel(['condition_type' => ConditionType::TRENDING, 'condition_value' => 'ignored']);
        (new Validator(new ConditionType()))->normalize($label);

        $this->assertNull($label->getConditionValue());
    }

    public function testSaleDefaultsToOnePercent(): void
    {
        $label = $this->createLabel(['condition_type' => ConditionType::SALE, 'condition_value' => '']);
        (new Validator(new ConditionType()))->normalize($label);

        $this->assertSame('1', $label->getConditionValue());
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidLabelsAreRejected(array $override, string $expectedFragment): void
    {
        $label = $this->createLabel($override);
        $validator = new Validator(new ConditionType());
        $validator->normalize($label);
        $errors = $validator->validate($label);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString($expectedFragment, implode(' ', $errors));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'missing name' => [['name' => ' '], 'Name is required'],
            'text too long' => [['text' => str_repeat('x', 41)], 'Label text'],
            'css injection colour' => [['background_color' => 'red;position:fixed'], 'hex colour'],
            'unknown type' => [['condition_type' => 'everything'], 'Choose which products'],
            'no skus' => [['condition_type' => ConditionType::SKU, 'condition_value' => ' , ;'], 'at least one SKU'],
            'bad category id' => [['condition_type' => ConditionType::CATEGORY, 'condition_value' => '4, x'], 'category IDs'],
            'discount 100' => [['condition_type' => ConditionType::SALE, 'condition_value' => '100'], 'between 1 and 99'],
            'impossible date' => [['from_date' => '2026-02-30'], 'valid calendar days'],
            'reversed range' => [['from_date' => '2026-12-01', 'to_date' => '2026-11-01'], 'on or after'],
        ];
    }

    public function testSplitList(): void
    {
        $this->assertSame(['A', 'B c', 'D'], (new Validator(new ConditionType()))->splitList(" A,B c\r\n\nD; A ,"));
        $this->assertSame([], (new Validator(new ConditionType()))->splitList('  '));
    }

    /**
     * Label model without database access.
     *
     * @param array<string, mixed> $override
     * @return Label
     */
    private function createLabel(array $override = []): Label
    {
        $label = new Label(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $this->createStub(LabelResource::class),
            $this->createStub(AbstractDb::class)
        );
        $label->setData(array_merge([
            'name' => 'Test label',
            'text' => 'Hot',
            'condition_type' => ConditionType::NEW,
            'condition_value' => null,
            'background_color' => '#2563EB',
            'text_color' => '#FFFFFF',
            'sort_order' => 0,
        ], $override));

        return $label;
    }
}
