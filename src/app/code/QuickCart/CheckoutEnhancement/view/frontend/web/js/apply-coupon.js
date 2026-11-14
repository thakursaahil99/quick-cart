/**
 * "Apply" buttons for advertised coupon codes (CMS block qc-cart-offers):
 * fills Magento's discount form on the cart page and submits it.
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        $(element).on('click', '[data-qc-coupon]', function () {
            var form = $('#discount-coupon-form'),
                input = $('#coupon_code');

            if (!form.length || !input.length) {
                return;
            }
            input.val(String($(this).data('qcCoupon')));
            form.find('#remove-coupon').val('0');
            form.trigger('submit');
        });
    };
});
