<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Model;

use Magento\Quote\Api\Data\AddressInterface;

/**
 * Server-side validation for QuickCart checkout data. Mirrors the client rules in
 * view/frontend/web/js/validation-mixin.js; the server is the source of truth.
 */
class CheckoutFieldsValidator
{
    public const NOTE_MAX_LENGTH = 250;
    public const COUNTRY_INDIA = 'IN';

    /**
     * GSTIN: 2-digit state code, PAN (5 letters, 4 digits, 1 letter), entity number,
     * the letter Z, and a check character.
     */
    private const GSTIN_PATTERN = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/';

    /**
     * Validate delivery instructions and GSTIN.
     *
     * @param string $note
     * @param string $gstin upper-cased
     * @return string[] error messages
     */
    public function validateMeta(string $note, string $gstin): array
    {
        $errors = [];
        if (mb_strlen($note) > self::NOTE_MAX_LENGTH) {
            $errors[] = (string)__(
                'Delivery instructions can be at most %1 characters.',
                self::NOTE_MAX_LENGTH
            );
        }
        if ($gstin !== '' && !$this->isValidGstin($gstin)) {
            $errors[] = (string)__('Enter a valid 15-character GSTIN, for example 27ABCDE1234F1Z5.');
        }

        return $errors;
    }

    /**
     * Validate phone number and PIN code of an Indian address.
     *
     * @param AddressInterface $address
     * @return string[] error messages
     */
    public function validateIndianAddress(AddressInterface $address): array
    {
        if (strtoupper((string)$address->getCountryId()) !== self::COUNTRY_INDIA) {
            return [];
        }
        $errors = [];
        if (!$this->isValidIndianPhone((string)$address->getTelephone())) {
            $errors[] = (string)__('Enter a valid 10-digit Indian mobile number.');
        }
        if (!$this->isValidIndianPin((string)$address->getPostcode())) {
            $errors[] = (string)__('Enter a valid 6-digit PIN code.');
        }

        return $errors;
    }

    /**
     * GSTIN format check.
     *
     * @param string $gstin
     * @return bool
     */
    public function isValidGstin(string $gstin): bool
    {
        return (bool)preg_match(self::GSTIN_PATTERN, strtoupper(trim($gstin)));
    }

    /**
     * Indian mobile number: 10 digits starting with 6-9, optional +91 / 91 / 0 prefix.
     *
     * @param string $phone
     * @return bool
     */
    public function isValidIndianPhone(string $phone): bool
    {
        $digits = (string)preg_replace('/[\s\-().]/', '', $phone);
        $digits = (string)preg_replace('/^(\+91|91(?=\d{10}$)|0(?=\d{10}$))/', '', $digits);

        return (bool)preg_match('/^[6-9]\d{9}$/', $digits);
    }

    /**
     * Indian PIN code: 6 digits, first digit 1-9.
     *
     * @param string $pin
     * @return bool
     */
    public function isValidIndianPin(string $pin): bool
    {
        return (bool)preg_match('/^[1-9][0-9]{5}$/', preg_replace('/\s/', '', $pin));
    }
}
