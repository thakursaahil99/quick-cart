/**
 * Small helpers shared by the QuickCart animation modules.
 */
define([], function () {
    'use strict';

    return {
        /**
         * Run a callback when a Motion animation finishes (controls are thenable).
         *
         * @param {Object} animation
         * @param {Function} callback
         */
        whenDone: function (animation, callback) {
            var promise = animation && (animation.finished || animation);

            if (promise && typeof promise.then === 'function') {
                promise.then(callback, callback);
            } else {
                callback();
            }
        },

        /**
         * Hand styling back to the stylesheet (so CSS :hover transforms work again).
         *
         * @param {Element[]} elements
         */
        clearInline: function (elements) {
            elements.forEach(function (element) {
                element.style.removeProperty('opacity');
                element.style.removeProperty('transform');
            });
        }
    };
});
