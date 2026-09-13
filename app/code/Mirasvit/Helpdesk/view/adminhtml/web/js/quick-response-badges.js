define([
    'underscore',
    'ko',
    'uiComponent',
    'Mirasvit_Helpdesk/js/quick-response',
    'jquery'
], function (_, ko, Component, QuickResponse, $) {
    'use strict';

    return QuickResponse.extend({
        currentTrigger: null,
        _previewActive: false,

        defaults: {
            closeOnOuter: false,
            _templates: [],
            templates: ko.observable(),
            template: 'Mirasvit_Helpdesk/quick-response-badges',
            listens: {},
            triggerPrefix: '#'
        },

        initialize: function () {
            this._super();

            this._templates = this.templates.toArray();

            this.templates = ko.observable([]);

            $('body').on('mst-hdmx-reply-content-change', function (e, text) {
                var trigger = this.extractTrigger(text);

                if (trigger) {
                    this.currentTrigger = trigger.expression;
                    this.templates(this.matchByKeywords(trigger.keywords));
                } else {
                    this.templates([]);
                    this.currentTrigger = null;
                }
            }.bind(this));

            return this;
        },

        extractTrigger: function (text) {
            if (!text) {
                return null;
            }

            var escapedPrefix = this.triggerPrefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            var regex = new RegExp(escapedPrefix + '([a-zA-Z0-9]+(?:_[a-zA-Z0-9]+)*)', 'g');
            var match, lastMatch = null;

            while ((match = regex.exec(text)) !== null) {
                lastMatch = match;
            }

            if (!lastMatch) {
                return null;
            }

            return {
                expression: lastMatch[0],
                keywords: lastMatch[1].split('_')
            };
        },

        extractKeywords: function (text) {
            if (!text) {
                return [];
            }

            return text.toLowerCase()
                .replace(/[^a-z0-9]/g, ' ')
                .split(' ')
                .filter(function (w) { return w.length > 0; });
        },

        matchByKeywords: function (keywords) {
            var self = this;
            var scored = [];

            this._templates.forEach(function (template) {
                var nameKeywords = self.extractKeywords(template.name || '');
                var bodyKeywords = self.extractKeywords(template.body || '');
                var nameSet = {}, bodySet = {};

                nameKeywords.forEach(function (kw) { nameSet[kw] = true; });
                bodyKeywords.forEach(function (kw) { bodySet[kw] = true; });

                var allSet = Object.assign({}, nameSet, bodySet);

                if (!keywords.every(function (kw) { return allSet[kw.toLowerCase()] === true; })) {
                    return;
                }

                var nameMatches = keywords.filter(function (kw) { return nameSet[kw.toLowerCase()] === true; }).length;

                scored.push({ template: template, nameMatches: nameMatches });
            });

            scored.sort(function (a, b) { return b.nameMatches - a.nameMatches; });

            return scored.map(function (item) { return item.template; });
        },

        showPreview: function (template) {
            this._previewActive = true;
            this._super(template);
        },

        hidePreview: function (template) {
            this._previewActive = false;
            this._super(template);
        },

        onChangeTemplate: function (template) {
            if (this._previewActive) {
                $('body').trigger('mst-hdmx-reply-preview-text', [template.body, false]);
                this._previewActive = false;
            }

            $('body').trigger('mst-hdmx-reply-replace-trigger', [this.currentTrigger, template.body]);
            $('body').trigger('mst-hdmx-reply-change');

            this.currentTrigger = null;
            this.templates([]);

            this.close();
        }
    });
});
