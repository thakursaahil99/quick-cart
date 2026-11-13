/**
 * Homepage hero slider ([data-qc-hero], content from the qc-home-hero CMS block).
 *
 * - First paint uses a CSS intro animation (no JS dependency, no flicker).
 * - Slide changes are choreographed with Motion: the copy leaves, then the next
 *   slide's copy staggers in while the product art springs into place.
 * - Autoplay with a progress indicator; pauses on hover, focus and hidden tabs.
 * - Arrow keys, dots and swipe; pointer parallax on the product art.
 */
define([], function () {
    'use strict';

    var INTERVAL = 6500,
        EASE_OUT = [0.22, 1, 0.36, 1],
        BACK_OUT = [0.34, 1.56, 0.64, 1];

    return function (Motion, reduced) {
        var root = document.querySelector('[data-qc-hero]'),
            slides,
            dots,
            current = 0,
            busy = false,
            timer = null,
            paused = false,
            startX = null;

        if (!root) {
            return;
        }
        slides = root.querySelectorAll('.qc-hero__slide');
        dots = root.querySelectorAll('[data-qc-hero-dot]');
        if (slides.length < 2) {
            return;
        }

        function then(animation, callback) {
            var promise = animation && (animation.finished || animation);

            if (promise && typeof promise.then === 'function') {
                promise.then(callback, callback);
            } else {
                callback();
            }
        }

        function markDots(index) {
            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === index);
                dot.setAttribute('aria-current', i === index ? 'true' : 'false');
            });
        }

        function schedule() {
            window.clearTimeout(timer);
            root.classList.remove('is-paused');
            if (!paused && !reduced) {
                timer = window.setTimeout(function () {
                    go((current + 1) % slides.length);
                }, INTERVAL);
            }
        }

        function enter(slide) {
            if (reduced) {
                return;
            }
            Motion.animate(slide.querySelectorAll('[data-qc-hero-item]'), {
                opacity: [0, 1],
                transform: ['translateY(28px)', 'translateY(0px)']
            }, {duration: 0.75, delay: Motion.stagger(0.08), ease: EASE_OUT});
            Motion.animate(slide.querySelector('[data-qc-hero-art]'), {
                opacity: [0, 1],
                transform: ['translateX(40px) scale(0.88) rotate(-6deg)', 'translateX(0px) scale(1) rotate(0deg)']
            }, {duration: 0.9, ease: BACK_OUT});
        }

        function go(next) {
            var from = slides[current],
                to = slides[next],
                leave;

            if (busy || next === current) {
                return;
            }
            busy = true;
            root.classList.add('is-interactive');
            leave = reduced ? null : Motion.animate(
                from.querySelectorAll('[data-qc-hero-item], [data-qc-hero-art]'),
                {opacity: [1, 0], transform: ['translateY(0px)', 'translateY(-18px)']},
                {duration: 0.32, ease: 'easeIn'}
            );

            then(leave, function () {
                from.classList.remove('is-active');
                from.setAttribute('aria-hidden', 'true');
                to.classList.add('is-active');
                to.removeAttribute('aria-hidden');
                root.setAttribute('data-accent', to.getAttribute('data-accent') || 'blue');
                enter(to);
                current = next;
                markDots(next);
                busy = false;
                schedule();
            });
        }

        slides.forEach(function (slide, i) {
            if (i !== 0) {
                slide.setAttribute('aria-hidden', 'true');
            }
        });
        root.setAttribute('data-accent', slides[0].getAttribute('data-accent') || 'blue');
        root.classList.add('is-ready');
        markDots(0);

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                go(parseInt(dot.getAttribute('data-qc-hero-dot'), 10) || 0);
            });
        });

        root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowRight') {
                go((current + 1) % slides.length);
            } else if (event.key === 'ArrowLeft') {
                go((current - 1 + slides.length) % slides.length);
            }
        });

        root.addEventListener('pointerdown', function (event) {
            startX = event.clientX;
        });
        root.addEventListener('pointerup', function (event) {
            var delta = startX === null ? 0 : event.clientX - startX;

            startX = null;
            if (Math.abs(delta) > 60) {
                go(delta < 0 ? (current + 1) % slides.length : (current - 1 + slides.length) % slides.length);
            }
        });

        function pause() {
            paused = true;
            window.clearTimeout(timer);
            root.classList.add('is-paused');
        }

        function resume() {
            paused = false;
            schedule();
        }

        root.addEventListener('mouseenter', pause);
        root.addEventListener('mouseleave', resume);
        root.addEventListener('focusin', pause);
        root.addEventListener('focusout', resume);
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                pause();
            } else {
                resume();
            }
        });

        // Pointer parallax: CSS reads the variables through the `translate` property,
        // so it composes with the floating keyframe animation on the image.
        if (!reduced && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            root.addEventListener('pointermove', function (event) {
                var rect = root.getBoundingClientRect();

                root.style.setProperty('--qc-px', ((event.clientX - rect.left) / rect.width - 0.5).toFixed(3));
                root.style.setProperty('--qc-py', ((event.clientY - rect.top) / rect.height - 0.5).toFixed(3));
            });
            root.addEventListener('pointerleave', function () {
                root.style.setProperty('--qc-px', '0');
                root.style.setProperty('--qc-py', '0');
            });
        }

        schedule();
    };
});
