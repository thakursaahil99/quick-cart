/**
 * Condensed sticky header: toggles body.qc-scrolled once the page scrolls.
 */
define([], function () {
    'use strict';

    return function () {
        var body = document.body,
            scrolled = null,
            ticking = false;

        function update() {
            var next = window.scrollY > 40;

            if (next !== scrolled) {
                body.classList.toggle('qc-scrolled', next);
                scrolled = next;
            }
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }, {passive: true});
        update();
    };
});
