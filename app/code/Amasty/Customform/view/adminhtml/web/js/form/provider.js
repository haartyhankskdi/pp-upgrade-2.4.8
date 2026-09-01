define([
    'Magento_Ui/js/form/provider'
], function (Provider) {
    return Provider.extend({
        save: function () {
            document.dispatchEvent(new Event('customFormSaveBefore'));

            return this._super();
        }
    });
});
