/**
 * QuickCart_ProductRecommendation - "Recently viewed".
 *
 * The history (product IDs + timestamps) lives in this browser's localStorage, so
 * product pages stay full-page cacheable and nothing is stored server-side. The
 * rail is rendered by GET qcreco/recent/render for the stored IDs.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var KEY = 'qc-recently-viewed',
        MAX_STORED = 20,
        MAX_AGE = 1000 * 60 * 60 * 24 * 60;

    /**
     * @returns {Array<{id: Number, ts: Number}>}
     */
    function read() {
        var list;

        try {
            list = JSON.parse(window.localStorage.getItem(KEY) || '[]');
        } catch (e) {
            return [];
        }

        return Array.isArray(list) ? list.filter(function (entry) {
            return entry && entry.id > 0 && Date.now() - entry.ts < MAX_AGE;
        }) : [];
    }

    /**
     * @param {Array} list
     */
    function write(list) {
        try {
            window.localStorage.setItem(KEY, JSON.stringify(list.slice(0, MAX_STORED)));
        } catch (e) {
            // Storage full or disabled (private mode): history is optional.
        }
    }

    /**
     * Move a product to the front of the history.
     *
     * @param {Number} productId
     */
    function track(productId) {
        var list = read().filter(function (entry) {
            return entry.id !== productId;
        });

        list.unshift({id: productId, ts: Date.now()});
        write(list);
    }

    return function (config, element) {
        var $root = $(element),
            exclude = parseInt(config.excludeId, 10) || 0,
            ids = read().map(function (entry) {
                return entry.id;
            }).filter(function (id) {
                return id !== exclude;
            }).slice(0, config.limit || 8);

        if (config.trackId) {
            track(parseInt(config.trackId, 10));
        }
        if (!ids.length) {
            return;
        }

        $.ajax({
            url: config.url,
            data: {ids: ids.join(',')},
            dataType: 'json',
            cache: false
        }).done(function (response) {
            if (response && response.html) {
                $root.html(response.html).addClass('is-loaded');
                $root.trigger('contentUpdated');
                animate($root.find('.qc-rail__item').get());
            }
        });
    };

    /**
     * Staggered entrance with Motion when the theme provides it (optional dependency).
     *
     * @param {Array<HTMLElement>} items
     */
    function animate(items) {
        if (!items.length || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        require(['motion'], function (Motion) {
            Motion.animate(items, {opacity: [0, 1], transform: ['translateY(24px)', 'translateY(0px)']}, {
                duration: 0.6,
                delay: Motion.stagger(0.06),
                ease: [0.22, 1, 0.36, 1]
            });
        }, function () {
            // Theme without Motion: content is already visible.
        });
    }
});
