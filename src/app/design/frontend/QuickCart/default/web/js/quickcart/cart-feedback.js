/**
 * Add-to-cart feedback.
 *
 * - Fly-to-cart: after Magento's catalogAddToCart succeeds ("ajax:addToCart"), a copy
 *   of the product image arcs into the mini cart.
 * - Cart bump: whenever the cart section of customer-data gains items (from any
 *   page or tab), the cart icon and counter pop.
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'js/quickcart/util'
], function ($, customerData, util) {
    'use strict';

    var EASE_IN_OUT = [0.65, 0, 0.35, 1];

    return function (Motion, reduced) {
        var cart = customerData.get('cart'),
            lastCount = Number((cart() || {}).summary_count || 0);

        function cartButton() {
            return document.querySelector('.minicart-wrapper .action.showcart');
        }

        function bump() {
            var button = cartButton(),
                counter;

            if (!button || reduced) {
                return;
            }
            Motion.animate(button, {transform: ['scale(1)', 'scale(1.28)', 'scale(0.94)', 'scale(1)']}, {
                duration: 0.55,
                ease: 'easeOut'
            });
            counter = button.querySelector('.counter.qty');
            if (counter) {
                Motion.animate(counter, {transform: ['scale(0.3)', 'scale(1.25)', 'scale(1)']}, {
                    duration: 0.5,
                    delay: 0.08
                });
            }
        }

        function sourceImage($form) {
            var $item;

            if ($form && $form.length) {
                $item = $form.closest('.product-item-info, .qc-card');
                if ($item.length) {
                    return $item.find('img.product-image-photo').get(0) || null;
                }
            }

            return document.querySelector(
                '.fotorama__stage__frame.fotorama__active img, .gallery-placeholder img, .product.media img'
            );
        }

        function fly($form) {
            var image = sourceImage($form),
                target = cartButton(),
                from,
                to,
                clone,
                dx,
                dy;

            if (!image || !target) {
                return;
            }
            from = image.getBoundingClientRect();
            to = target.getBoundingClientRect();
            if (!from.width || !to.width) {
                return;
            }

            clone = image.cloneNode(false);
            clone.removeAttribute('loading');
            clone.removeAttribute('id');
            clone.className = 'qc-fly';
            clone.setAttribute('aria-hidden', 'true');
            clone.style.left = from.left + 'px';
            clone.style.top = from.top + 'px';
            clone.style.width = from.width + 'px';
            clone.style.height = from.height + 'px';
            document.body.appendChild(clone);

            dx = to.left + to.width / 2 - (from.left + from.width / 2);
            dy = to.top + to.height / 2 - (from.top + from.height / 2);

            util.whenDone(Motion.animate(clone, {
                transform: [
                    'translate(0px, 0px) scale(1) rotate(0deg)',
                    'translate(' + dx * 0.45 + 'px, ' + (dy * 0.45 - 90) + 'px) scale(0.55) rotate(-8deg)',
                    'translate(' + dx + 'px, ' + dy + 'px) scale(0.1) rotate(0deg)'
                ],
                opacity: [1, 1, 0.3]
            }, {duration: 0.9, ease: EASE_IN_OUT}), function () {
                clone.remove();
            });
        }

        cart.subscribe(function (data) {
            var count = Number((data || {}).summary_count || 0);

            if (count > lastCount) {
                bump();
            }
            lastCount = count;
        });

        $(document).on('ajax:addToCart', function (event, data) {
            if (!reduced) {
                fly(data && data.form ? $(data.form) : null);
            }
        });
    };
});
