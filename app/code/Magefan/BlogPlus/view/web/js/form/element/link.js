/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'Magento_Ui/js/form/element/abstract'
], function (AbstractElement) {
    'use strict';

    return AbstractElement.extend({
        defaults: {
            elementTmpl: 'Magefan_BlogPlus/form/element/link'
        },

        initialize: function () {
            this._super();

            var value = this.value();
            this.url = value.url;
            this.title = value.title;
            this.text = value.text;
        },

    });
});