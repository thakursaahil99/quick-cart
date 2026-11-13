/**
 * QuickCart_CatalogEnhancement - product rail controller.
 *
 * Native CSS scroll-snap does the scrolling (touch, trackpad, keyboard all work);
 * this component wires the prev/next buttons and keeps their disabled state in sync.
 */
define([], function () {
    'use strict';

    return function (config, section) {
        var viewport = section.querySelector('[data-role=viewport]'),
            prev = section.querySelector('[data-role=prev]'),
            next = section.querySelector('[data-role=next]'),
            reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            ticking = false;

        if (!viewport || !prev || !next) {
            return;
        }

        function update() {
            var max = viewport.scrollWidth - viewport.clientWidth - 2;

            prev.disabled = viewport.scrollLeft <= 2;
            next.disabled = viewport.scrollLeft >= max;
            section.classList.toggle('is-scrollable', max > 0);
            ticking = false;
        }

        function page(direction) {
            viewport.scrollBy({
                left: direction * viewport.clientWidth * 0.85,
                behavior: reduceMotion ? 'auto' : 'smooth'
            });
        }

        prev.addEventListener('click', function () {
            page(-1);
        });
        next.addEventListener('click', function () {
            page(1);
        });
        viewport.addEventListener('scroll', function () {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }, {passive: true});
        window.addEventListener('resize', update, {passive: true});
        update();
    };
});
