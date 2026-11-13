/**
 * Scroll-reveal: [data-qc-reveal] fades/slides in, [data-qc-stagger] reveals its
 * children one after another. Only content below the fold is hidden first, so
 * nothing visible on load ever flickers.
 */
define(['js/quickcart/util'], function (util) {
    'use strict';

    var EASE = [0.22, 1, 0.36, 1];

    return function (Motion) {
        var fold = window.innerHeight * 0.92,
            options = {margin: '0px 0px -8% 0px'};

        function belowFold(element) {
            return element.getBoundingClientRect().top > fold;
        }

        function hide(elements, distance) {
            elements.forEach(function (element) {
                element.style.opacity = '0';
                element.style.transform = 'translateY(' + distance + 'px)';
            });
        }

        document.querySelectorAll('[data-qc-reveal]').forEach(function (section) {
            if (!belowFold(section)) {
                return;
            }
            hide([section], 36);
            Motion.inView(section, function () {
                util.whenDone(
                    Motion.animate(section, {opacity: 1, transform: 'translateY(0px)'}, {duration: 0.8, ease: EASE}),
                    function () {
                        util.clearInline([section]);
                    }
                );
            }, options);
        });

        document.querySelectorAll('[data-qc-stagger]').forEach(function (list) {
            var items = Array.prototype.slice.call(list.children);

            if (!items.length || !belowFold(list)) {
                return;
            }
            hide(items, 28);
            Motion.inView(list, function () {
                util.whenDone(
                    Motion.animate(items, {opacity: 1, transform: 'translateY(0px)'}, {
                        duration: 0.65,
                        delay: Motion.stagger(0.07),
                        ease: EASE
                    }),
                    function () {
                        util.clearInline(items);
                    }
                );
            }, options);
        });
    };
});
