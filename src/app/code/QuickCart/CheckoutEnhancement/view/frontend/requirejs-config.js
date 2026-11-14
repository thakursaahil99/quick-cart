/**
 * QuickCart_CheckoutEnhancement RequireJS configuration.
 */
var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/shipping-save-processor/payload-extender': {
                'QuickCart_CheckoutEnhancement/js/model/payload-extender-mixin': true
            },
            'Magento_Checkout/js/view/shipping': {
                'QuickCart_CheckoutEnhancement/js/view/shipping-mixin': true
            },
            'Magento_Ui/js/lib/validation/validator': {
                'QuickCart_CheckoutEnhancement/js/validation-mixin': true
            }
        }
    }
};
