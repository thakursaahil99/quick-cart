/**
 * QuickCart storefront interactions (loaded on every page via requirejs-config deps).
 *
 * Built on Motion (motion.dev), the framework-agnostic engine behind Framer Motion.
 * All effects respect prefers-reduced-motion, and content is server-rendered and
 * visible without JavaScript.
 */
define([
    'motion',
    'js/quickcart/reveal',
    'js/quickcart/hero',
    'js/quickcart/tilt',
    'js/quickcart/header',
    'js/quickcart/cart-feedback',
    'domReady!'
], function (Motion, reveal, hero, tilt, header, cartFeedback) {
    'use strict';

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        body = document.body,
        bar;

    document.documentElement.classList.add('qc-js');
    header();
    hero(Motion, reduced);
    cartFeedback(Motion, reduced);

    if (reduced) {
        return;
    }

    reveal(Motion);
    tilt(Motion);

    // Tactile press feedback on primary buttons.
    Motion.press('.qc-btn, .action.primary, .action.tocart', function (element) {
        Motion.animate(element, {transform: 'scale(0.96)'}, {duration: 0.12});

        return function () {
            Motion.animate(element, {transform: 'scale(1)'}, {duration: 0.35, ease: [0.34, 1.56, 0.64, 1]});
        };
    });

    // Reading progress bar on product pages, driven by Motion's scroll timeline.
    if (body.classList.contains('catalog-product-view')) {
        bar = document.createElement('div');
        bar.className = 'qc-progress';
        bar.setAttribute('aria-hidden', 'true');
        body.appendChild(bar);
        Motion.scroll(Motion.animate(bar, {transform: ['scaleX(0)', 'scaleX(1)']}, {ease: 'linear'}));
    }
});
