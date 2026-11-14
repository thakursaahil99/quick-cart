/**
 * QuickCart_CatalogEnhancement - grid column rendering a label rule as its storefront pill.
 */
define([
    'Magento_Ui/js/grid/columns/column'
], function (Column) {
    'use strict';

    var HEX = /^#[0-9A-F]{6}$/i;

    return Column.extend({
        defaults: {
            bodyTmpl: 'QuickCart_CatalogEnhancement/grid/cells/label-preview'
        },

        /**
         * Pill colours; invalid values fall back to the brand defaults.
         *
         * @param {Object} row
         * @returns {Object}
         */
        getPillStyle: function (row) {
            return {
                backgroundColor: HEX.test(row.background_color) ? row.background_color : '#2563EB',
                color: HEX.test(row.text_color) ? row.text_color : '#FFFFFF'
            };
        },

        /**
         * Label text with a sample discount for the "{discount}" token.
         *
         * @param {Object} row
         * @returns {String}
         */
        getPillText: function (row) {
            return String(row[this.index] || '').replace('{discount}', '20');
        }
    });
});
