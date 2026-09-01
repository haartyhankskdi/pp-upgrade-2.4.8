define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/element/abstract'
], function ($, _, Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {

            listens: {
                'pageGroup': 'onUpdateField',
                'pageGroupType': 'onUpdateField'
            },
        },

        initObservable: function () {
            this._super();
            this.observe(['chooserHtml', 'chooserVisible']);
            return this;
        },

        onUpdateField: function(pageGroup) {
            var pageLayout = _(this.pageLayouts).chain(). pluck('optgroup').flatten().findWhere({value: this.pageGroup}).value();
            if (pageLayout && pageLayout.url && this.pageGroupType == 'specific') {
                if (this.currentPageGroup && this.currentPageGroup != pageGroup) {
                    this.value('');
                }
                this.currentPageGroup = pageGroup;
                this.visible(true);
            } else {
                this.visible(false);
            }
            this.chooserVisible(false);
            this.chooserHtml('');
        },

        showChooser: function() {
            this.chooserVisible(true);
            var self = this;
            var pageLayout = _(this.pageLayouts).chain(). pluck('optgroup').flatten().findWhere({value: this.pageGroup}).value();
            if (!self.chooserHtml() && pageLayout) {
                $('body').trigger('processStart');
                $.ajax({
                    url: pageLayout.url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        selected: self.value(),
                        id: self.uid,
                        type: pageLayout.value
                    },

                    success: function (res) {
                        var html = res.html ? res.html : '';
                        self.chooserHtml(html);
                        if (res.html) self.chooserVisible(true);

                        // Category Grid
                        $('#tree' + self.uid).on('node:changed', function(event, data) {
                            var ids = [];
                            var value = self.value();
                            var node = node = data.node;
                            if (node.attributes.checked) {
                                if (value) ids = value.toString().split(',');
                                else ids = [];
                                if (-1 == ids.indexOf(node.id)) {
                                    ids.push(node.id);
                                }
                            } else {
                                ids = value.split(',');
                                while (-1 != ids.indexOf(node.id)) {
                                    ids.splice(ids.indexOf(node.id), 1);
                                }
                            }
                            self.value(ids.join(','));
                        });

                        // Grid: Product, Page
                        $('#grid' + self.uid).on('grid:changed', function(event, data) {
                            var ids   = [];
                            var value = self.value();
                            var elm   = data.element;
                            if ($(elm).is(':checked')) {
                                if (value) ids = value.split(',');
                                else ids = [];
                                if (-1 == ids.indexOf($(elm).val())) {
                                    ids.push($(elm).val());
                                }
                            } else {
                                ids = value.split(',');
                                while (-1 != ids.indexOf(node.id)) {
                                    ids.splice(ids.indexOf(node.id), 1);
                                }
                            }
                            self.value(ids.join(','));
                        });
                    },

                    /**
                     * Complete callback.
                     */
                    complete: function () {
                        $('body').trigger('processStop');
                    }
                });
            }
        },

        hideChooser: function() {
            this.chooserVisible(false);
        }
    });
});
