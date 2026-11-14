<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Test\Unit\Model;

use Magento\Quote\Api\Data\AddressInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickCart\CheckoutEnhancement\Model\CheckoutFieldsValidator;

#[CoversClass(CheckoutFieldsValidator::class)]
class CheckoutFieldsValidatorTest extends TestCase
{
    /**
     * @var CheckoutFieldsValidator
     */
    private CheckoutFieldsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CheckoutFieldsValidator();
    }

    #[DataProvider('gstinProvider')]
    public function testGstin(string $gstin, bool $valid): void
    {
        $this->assertSame($valid, $this->validator->isValidGstin($gstin));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function gstinProvider(): array
    {
        return [
            'valid'               => ['27ABCDE1234F1Z5', true],
            'lower case accepted' => ['29aagcb1286q1z9', true],
            'too short'           => ['27ABCDE1234F1Z', false],
            'missing Z'           => ['27ABCDE1234F1X5', false],
            'letters in state'    => ['AAABCDE1234F1Z5', false],
        ];
    }

    #[DataProvider('phoneProvider')]
    public function testIndianPhone(string $phone, bool $valid): void
    {
        $this->assertSame($valid, $this->validator->isValidIndianPhone($phone));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function phoneProvider(): array
    {
        return [
            'plain'          => ['9876543210', true],
            'with +91'       => ['+91 98765 43210', true],
            'with 0'         => ['09876543210', true],
            'with 91'        => ['919876543210', true],
            'dashes'         => ['98765-43210', true],
            'starts with 5'  => ['5876543210', false],
            'nine digits'    => ['987654321', false],
            'letters'        => ['98765abcde', false],
        ];
    }

    #[DataProvider('pinProvider')]
    public function testIndianPin(string $pin, bool $valid): void
    {
        $this->assertSame($valid, $this->validator->isValidIndianPin($pin));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function pinProvider(): array
    {
        return [
            'Mumbai'        => ['400001', true],
            'with space'    => ['560 001', true],
            'leading zero'  => ['012345', false],
            'five digits'   => ['40000', false],
        ];
    }

    public function testMetaErrors(): void
    {
        $this->assertSame([], $this->validator->validateMeta('Leave with the security desk', ''));
        $this->assertCount(1, $this->validator->validateMeta(str_repeat('x', 251), ''));
        $this->assertCount(1, $this->validator->validateMeta('', 'NOTAGSTIN'));
    }

    public function testAddressRulesOnlyApplyToIndia(): void
    {
        $indian = $this->createAddress('IN', '12345', '400001');
        $american = $this->createAddress('US', '12345', '10001');

        $this->assertCount(1, $this->validator->validateIndianAddress($indian));
        $this->assertSame([], $this->validator->validateIndianAddress($american));
    }

    private function createAddress(string $country, string $phone, string $postcode): AddressInterface
    {
        $address = $this->createStub(AddressInterface::class);
        $address->method('getCountryId')->willReturn($country);
        $address->method('getTelephone')->willReturn($phone);
        $address->method('getPostcode')->willReturn($postcode);

        return $address;
    }
}
