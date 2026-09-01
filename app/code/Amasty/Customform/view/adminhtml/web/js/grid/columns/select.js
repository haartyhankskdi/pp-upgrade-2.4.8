define([
    'jquery',
    'underscore',
    'Magento_Ui/js/grid/columns/select',
    'mage/translate'
], function ($, _, Select) {
    'use strict';

    return Select.extend({
        /**
         * Retrieves label associated with a provided value.
         *
         * @returns {String}
         */
        getLabel: function (record) {
            var label = this._super(record);

            if (!label) {
                label = record.form_name ? record.form_name : $.mage.__(' Form#') + record.form_id
                label = label + $.mage.__(' (removed)');
            }

            return label;
        }
    });
});
