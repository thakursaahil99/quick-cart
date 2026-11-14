/**
 * Client-side rules (instant feedback). The server repeats these checks in
 * QuickCart\CheckoutEnhancement\Model\CheckoutFieldsValidator.
 */
define([
    'jquery',
    'uiRegistry',
    'mage/translate'
], function ($, registry) {
    'use strict';

    /**
     * Phone/PIN rules apply only when the shipping country is India.
     *
     * @return {Boolean}
     */
    function shippingToIndia() {
        var provider = registry.get('checkoutProvider'),
            country = provider ? provider.get('shippingAddress.country_id') : '';

        return !country || country === 'IN';
    }

    return function (validator) {
        validator.addRule(
            'qc-gstin',
            function (value) {
                value = $.trim(value || '').toUpperCase();

                return value === '' || /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(value);
            },
            $.mage.__('Enter a valid 15-character GSTIN, for example 27ABCDE1234F1Z5.')
        );

        validator.addRule(
            'qc-in-phone',
            function (value) {
                var digits = (value || '').replace(/[\s\-().]/g, '').replace(/^(\+91|91(?=\d{10}$)|0(?=\d{10}$))/, '');

                return !shippingToIndia() || /^[6-9]\d{9}$/.test(digits);
            },
            $.mage.__('Enter a valid 10-digit Indian mobile number.')
        );

        validator.addRule(
            'qc-in-pincode',
            function (value) {
                return !shippingToIndia() || /^[1-9][0-9]{5}$/.test((value || '').replace(/\s/g, ''));
            },
            $.mage.__('Enter a valid 6-digit PIN code.')
        );

        return validator;
    };
});
