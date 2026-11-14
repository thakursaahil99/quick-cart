/**
 * Sends the "Delivery details" form with the shipping step as
 * addressInformation.extension_attributes.qc_delivery_note / qc_gstin.
 */
define([
    'mage/utils/wrapper',
    'uiRegistry'
], function (wrapper, registry) {
    'use strict';

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalExtender, payload) {
            var result = originalExtender(payload),
                provider = registry.get('checkoutProvider'),
                data = (provider && provider.get('qcCheckout')) || {};

            result.addressInformation['extension_attributes'] = result.addressInformation['extension_attributes'] || {};
            result.addressInformation['extension_attributes']['qc_delivery_note'] = (data.delivery_note || '').trim();
            result.addressInformation['extension_attributes']['qc_gstin'] = (data.gstin || '').trim().toUpperCase();

            return result;
        });
    };
});
