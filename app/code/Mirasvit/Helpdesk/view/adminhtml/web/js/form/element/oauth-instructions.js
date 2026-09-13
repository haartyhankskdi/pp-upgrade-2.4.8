define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    // A non-editing display block: it renders an instructions template and is
    // shown/hidden by authorization-type.js exactly like the other OAuth2 fields.
    return Abstract.extend({
        defaults: {
            visible: false
        }
    });
});
