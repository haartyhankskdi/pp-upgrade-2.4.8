define([
    'underscore',
    'ko',
    'uiComponent',
    'Mirasvit_Helpdesk/js/quick-response',
    'jquery',
    'Mirasvit_Helpdesk/js/lib/bm25'
], function (_, ko, Component, QuickResponse, $, FlexSearch) {
    'use strict';
    
    return QuickResponse.extend({
        bmSearch: null,
        
        defaults: {
            closeOnOuter: false,
            _templates: [],
            templates: ko.observable(),
            template: 'Mirasvit_Helpdesk/quick-response-badges',
            listens: {}
        },
    
        initialize: function () {
            this._super();
    
            this._templates = this.templates.toArray();
            
            this.templates = ko.observable();
            
            this.bmSearch = new BM25;
    
            _.each(this._templates, function (template) {
                if (template.body) {
                    this.bmSearch.addDocument(template);
                }
            }.bind(this));
    
            this.bmSearch.updateIdf();
    
            $('body').on('mst-hdmx-reply-content-change', function (e, text) {
                this.search(text);
            }.bind(this));
        
            return this;
        },
    
        search: function (body) {
            if (!body) {
                this.templates([]);
                
                return;
            }
    
            let results = this.bmSearch.search(body);
            
            this.templates(results);
        }
    });
});
