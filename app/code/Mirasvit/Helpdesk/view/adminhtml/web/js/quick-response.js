define([
    'underscore',
    'ko',
    'uiComponent',
    'Magento_Ui/js/lib/collapsible',
    'jquery'
], function (_, ko, Component, Collapsible, $) {
    'use strict';

    return Collapsible.extend({
        current: null,
    
        skipPreview: false,
    
        isClickedBadge: false,
    
        defaults: {
            closeOnOuter: false,
            templates:    [],
            template:     'Mirasvit_Helpdesk/quick-response',
            listens:      {}
        },
    
        initialize: function () {
            this._super();
        
            this.current = ko.observable();
        
            _.bindAll(this, 'onChangeTemplate');
            _.bindAll(this, 'showPreview');
            _.bindAll(this, 'hidePreview');
        
            this.current(this.templates[0]);
        
            return this;
        },
    
        onChangeTemplate: function (template) {
            this.set('current', template);
            this.close();
        
            template.skipPreview = true;
        
            $('body').trigger('mst-hdmx-reply-preview-text', [template.body, false]);
        
            template.isClickedBadge = true;
        
            $('body').trigger('mst-hdmx-reply-add-text', template.body);
            $('body').trigger('mst-hdmx-reply-change');
        
            template.skipPreview = false;
        
        
            this.set('current', this.templates[0]);
        },
    
        showPreview: function (template) {
            $('body').trigger('mst-hdmx-reply-preview-text', [template.body, true]);
        
            if (template.isClickedBadge) {
                template.isClickedBadge = false;
            }
        },
    
        hidePreview: function (template) {
            if (!template.skipPreview && !template.isClickedBadge) {

                $('body').trigger('mst-hdmx-reply-preview-text', [template.body, false]);
            }
        }
    });
});
