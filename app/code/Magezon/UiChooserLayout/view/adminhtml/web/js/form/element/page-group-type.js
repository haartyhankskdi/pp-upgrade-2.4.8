define([
    'jquery',
    'Magezon_UiBuilder/js/form/element/ui-select'
], function ($, Select) {
    'use strict';

    return Select.extend({
        defaults: {
            loadedChooser: false,

            listens: {
                'pageGroup': 'processingInsertData',
                'pageGroupType': 'processingInsertData'
            }
        },

        processingInsertData: function(pageGroup) {
            var pageLayout = _(this.pageLayouts).chain(). pluck('optgroup').flatten().findWhere({value: this.pageGroup}).value();
            if (pageLayout && pageLayout.url) {
                this.visible(true);
            } else {
                this.visible(false);
            }
        }
    });
});
