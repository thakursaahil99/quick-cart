/**
 * 3D tilt + light glare for [data-qc-tilt] cards (category tiles, promos).
 * Fine pointers only; touch devices keep the flat layout.
 */
define([], function () {
    'use strict';

    var SETTLE = [0.34, 1.56, 0.64, 1];

    return function (Motion) {
        if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            return;
        }

        document.querySelectorAll('[data-qc-tilt]').forEach(function (card) {
            var rect = null;

            card.addEventListener('pointerenter', function () {
                rect = card.getBoundingClientRect();
            });

            card.addEventListener('pointermove', function (event) {
                var x, y;

                rect = rect || card.getBoundingClientRect();
                x = (event.clientX - rect.left) / rect.width - 0.5;
                y = (event.clientY - rect.top) / rect.height - 0.5;
                card.style.setProperty('--qc-glare-x', ((x + 0.5) * 100).toFixed(1) + '%');
                card.style.setProperty('--qc-glare-y', ((y + 0.5) * 100).toFixed(1) + '%');
                Motion.animate(card, {
                    transform: 'perspective(900px) rotateX(' + (-y * 8).toFixed(2) + 'deg) rotateY(' +
                        (x * 10).toFixed(2) + 'deg) translateY(-6px)'
                }, {duration: 0.25, ease: 'easeOut'});
            });

            card.addEventListener('pointerleave', function () {
                rect = null;
                Motion.animate(card, {
                    transform: 'perspective(900px) rotateX(0deg) rotateY(0deg) translateY(0px)'
                }, {duration: 0.6, ease: SETTLE});
            });
        });
    };
});
