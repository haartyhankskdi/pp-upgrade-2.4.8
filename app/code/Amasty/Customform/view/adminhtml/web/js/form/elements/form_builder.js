define([
    'jquery',
    'Magento_Ui/js/form/components/html',
    'Amasty_Customform/js/form-builder-helper'
], function ($, HTMLComponent, helpers) {
    'use strict';

    return HTMLComponent.extend({
        /**
         * @return {Object} - reference to instance
         */
        initObservable: function () {
            this._super();
            $(document).on('customFormSaveBefore', this.saveFormConfig.bind(this));

            return this;
        },

        saveFormConfig: function () {
            var formBuilderWidget,
                formTitles,
                formConfig;

            if ($.mage.customFormBuilder) {
                formBuilderWidget = $.mage.customFormBuilder.prototype;

                if (!formBuilderWidget.helpers) {
                    formBuilderWidget.helpers = helpers[1]({}, formBuilderWidget);
                }

                formTitles = formBuilderWidget.getPageTitles();
                formConfig = formBuilderWidget.getSerializedFormConfig();
                this.source.set('data.form_json', JSON.stringify(formConfig));
                this.source.set('data.form_title', JSON.stringify(formTitles));
                delete formBuilderWidget.helpers;
            }
        }
    });
});
