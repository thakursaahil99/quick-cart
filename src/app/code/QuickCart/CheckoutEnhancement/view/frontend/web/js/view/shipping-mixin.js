/**
 * Validates the "Delivery details" form together with the shipping address
 * before the customer can continue to payment.
 */
define([], function () {
    'use strict';

    return function (Shipping) {
        return Shipping.extend({
            /**
             * @return {Boolean}
             */
            validateShippingInformation: function () {
                if (!this._super()) {
                    return false;
                }
                this.source.set('params.invalid', false);
                this.source.trigger('qcCheckout.data.validate');

                return !this.source.get('params.invalid');
            }
        });
    };
});
