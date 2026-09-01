define([
    'jquery',
    'Magento_Ui/js/grid/export',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, mageExport, alert) {
    'use strict';

    return mageExport.extend({
        defaults: {
            emptySelectionProcess: [
                'individual_pdfs'
            ]
        },

        /**
         *
         * @param {object} option
         * @return string|null
         */
        buildOptionUrl: function (option) {
            var params,
                emptySelection;

            if (this.emptySelectionProcess.indexOf(option.value) !== -1) {
                params = this.getParams();

                if (Boolean(params['empty_selection'])) {
                    alert({
                        title: $.mage.__('Attention'),
                        content: $.mage.__('You haven’t selected any items!')
                    });

                    return null;
                }
            }

            return this._super();
        },

        /**
         * @return {object}
         */
        getParams: function () {
            var params = this._super(),
                hasSelected = Array.isArray(params.selected) && params.selected.length > 0,
                hasExcluded = (Array.isArray(params.excluded) && params.excluded.length) || params.excluded === false;

            params.empty_selection = !hasSelected && !hasExcluded;

            return params;
        },

        applyOption: function () {
            var option = this.getActiveOption(),
                url = this.buildOptionUrl(option);

            if (url !== null) {
                window.open(url);
            }
        }
    });
});
