/**
 * Free-delivery progress meter (mini cart + cart page).
 * Reads "qc_free_delivery" from the private cart section of customer-data.
 */
define([
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data'
], function (Component, ko, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'QuickCart_CheckoutEnhancement/free-delivery'
        },

        /**
         * @return {Object}
         */
        initialize: function () {
            var cart = customerData.get('cart');

            this._super();
            this.progress = ko.pureComputed(function () {
                var data = cart() || {};

                return data['qc_free_delivery'] && data['summary_count'] > 0 ? data['qc_free_delivery'] : null;
            });
            this.isVisible = ko.pureComputed(function () {
                return !!(this.progress() && this.progress().enabled);
            }, this);
            this.isUnlocked = ko.pureComputed(function () {
                return !!(this.progress() && this.progress().unlocked);
            }, this);
            this.percent = ko.pureComputed(function () {
                return this.progress() ? this.progress().percent : 0;
            }, this);
            this.remaining = ko.pureComputed(function () {
                return this.progress() ? this.progress()['remaining_formatted'] : '';
            }, this);

            return this;
        }
    });
});
