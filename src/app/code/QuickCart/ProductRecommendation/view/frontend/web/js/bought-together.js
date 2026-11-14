/**
 * QuickCart_ProductRecommendation - "Frequently bought together".
 *
 * Keeps the bundle total in sync with the ticked companions and adds them through the
 * product form's native "related_product" field, so the main product's own options
 * and validation (swatches, qty) still apply and Magento adds everything in one request.
 */
define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function ($, priceUtils, $t) {
    'use strict';

    return function (config, element) {
        var $root = $(element),
            $form = $(config.formSelector || '#product_addtocart_form');

        /**
         * @returns {jQuery}
         */
        function checked() {
            return $root.find('input[data-role="fbt-item"]:checked');
        }

        /**
         * Refresh total, item count and the dimmed state of unticked rows.
         */
        function update() {
            var total = parseFloat(config.basePrice) || 0,
                count = 1;

            checked().each(function () {
                total += parseFloat(this.getAttribute('data-price')) || 0;
                count++;
            });
            $root.find('input[data-role="fbt-item"]').each(function () {
                $(this).closest('[data-role="fbt-row"]').toggleClass('is-off', !this.checked);
            });
            $root.find('[data-role="total"]').text(priceUtils.formatPrice(total, config.priceFormat));
            $root.find('[data-role="count"]').text(
                count === 1 ? $t('Total for this item') : $t('Total for %1 items').replace('%1', count)
            );
            $root.find('[data-role="add"] span').text(
                count === 1 ? $t('Add this item to cart') : $t('Add all %1 to cart').replace('%1', count)
            );
        }

        /**
         * Submit the product form with the ticked companions as related products.
         */
        function addSelected() {
            var $field = $form.find('input[name="related_product"]'),
                previous,
                related = [];

            if (!$form.length) {
                return;
            }
            if (!$field.length) {
                $field = $('<input type="hidden" name="related_product">').appendTo($form);
            }
            previous = $field.val();
            // Keep products ticked in Magento's own "related products" block as well.
            $('.related-checkbox:checked').each(function () {
                related.push(this.value);
            });
            checked().each(function () {
                related.push(this.value);
            });
            $field.val($.unique(related).join(','));

            // Validation and serialisation happen synchronously inside the click handler.
            $form.find('#product-addtocart-button').trigger('click');
            $field.val(previous);

            if ($form.find('.mage-error:visible, div.mage-error').length) {
                $('html, body').animate({scrollTop: $form.offset().top - 120}, 300);
            }
        }

        $root.on('change', 'input[data-role="fbt-item"]', update);
        $root.on('click', '[data-role="add"]', addSelected);
        update();
    };
});
