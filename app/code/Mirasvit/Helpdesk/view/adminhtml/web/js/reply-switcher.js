define([
    'underscore',
    'ko',
    'uiComponent',
    'Magento_Ui/js/lib/collapsible',
    'jquery',
    'mage/url'
], function (_, ko, Component, Collapsible, $, url) {
    'use strict';
    let replySelector = '[data-field=helpdesk-reply-field] textarea';

    return Collapsible.extend({
        current: ko.observable(0),
        defaults: {
            closeOnOuter: false,
            stores: [
                {"id": "public", "label": $.mage.__("Public Reply"), note: $.mage.__("Your message will be emailed to the customer")},
                {"id": "internal", "label": $.mage.__("Internal Note"), note: $.mage.__("Your message will be emailed to your colleague. The customer will not see it.")},
                {"id": "public_third", "label": $.mage.__("Message to Third Party"), note: $.mage.__("Your message will be emailed to the third party. Customer will see it in the ticket history.")},
                {"id": "internal_third", "label": $.mage.__("Internal Message to Third Party"), note: $.mage.__("Your message will be emailed to the third party. The customer will not see it.")}
            ],
            template: 'Mirasvit_Helpdesk/reply-switcher',
            listens: {}
        },

        initialize: function () {
            this._super();
            _.bindAll(this, 'onChangeStore');
            this.current(this.stores[0]);

            $(document).ready(function() {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        var $textarea = $('[data-field=helpdesk-reply-field] textarea');
                        if ($textarea.length) {
                            var store = this.current();
                            $textarea.attr('placeholder', store.note);
                            observer.disconnect();
                        }
                    }.bind(this));
                }.bind(this));

                observer.observe(document.body, { childList: true, subtree: true });

            }.bind(this));
            return this;
        },

        onChangeStore: function (store) {
            this.set('current', store);
            this.close();
            $('body').trigger('mst-hdmx-switch-reply-type', store.id);
            var $textarea = $('[data-field=helpdesk-reply-field] textarea');
            if ($textarea.length) {
                var placeholderText = store.note;
                $textarea.attr('placeholder', placeholderText);
            }
        },

        isThirdParty: function () {
            return this.current().id == 'public_third' || this.current().id == 'internal_third';
        },
        imgUrl: function () {
            var img = document.getElementById('hdmx-attachment-image');
            var imgUrl = img.src;
            return imgUrl;
        },
        attachmentSize: function () {
            var maxAttachmentSizeElement = document.getElementById('max-attachment-size');
            var maxAttachmentSize = maxAttachmentSizeElement.value;
            return maxAttachmentSize;
        },

        afterFileInputRender: function () {
            var $fileInput = $('#hdmx_attachment');

            $fileInput.MultiFile({
                list: '#hdmx__reply-attach-list',
            });

            function attachChangeHandler() {
                var $input = $('#hdmx_attachment_wrap input[type="file"]');
                if ($input.length) {
                    $input.off('.hdmx').on('change.hdmx', function () {
                        setTimeout(function () {
                            var newSrc = document.getElementById('hdmx-plug_item').src;
                            $('#hdmx__reply-attach-list > .MultiFile-label').each(function () {
                                var $img = $(this).find('img.MultiFile-preview');
                                if ($img.length) {
                                    var src = $img.attr('src');
                                    if (!src || !src.startsWith('data:image')) {
                                        $img.attr('src', newSrc);
                                    }
                                }
                            });
                        }, 100);
                    });
                }
            }

            // Initial attach
            attachChangeHandler();

            // Observe for new file input elements added by MultiFile
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.addedNodes.length) {
                        attachChangeHandler();
                    }
                });
            });

            observer.observe(document.getElementById('hdmx_attachment_wrap'), {
                childList: true,
                subtree: true
            });


            var $replyArea = $(replySelector);
            var updateLabel = function () {
                var newId = $('#hdmx_attachment_wrap').find('input').attr('id');
                $('#hdmx_attachment-label').attr('for', newId);
            };

            setInterval(function() {
                updateSaveBtn();
                updateLabel();
            }, 500);

            var updateSaveBtn = function () {
                var saveButton = $('#save-split-button-save-button,#save-split-button-button,#save-split-button-close-button');
                var editButton = $('#save-split-button-save-continue-button,#save-split-button-edit-button');

                if ($replyArea.val() == '') {
                    saveButton.html('Save');
                    editButton.html('Save & Continue Edit');
                } else {
                    saveButton.html('Save & Send Message');
                    editButton.html('Save, Send & Continue Edit');
                }
            };

            setTimeout(function() {
                updateTextarea();
                updateWysiwyg(); // wysiwyg does not show from first time

                $(replySelector).autoGrow();
            }, 500);

            var updateTextarea = function () {
                $('body').trigger('mst-hdmx-switch-reply-type', $('[data-field="reply_type"]').val());
            };

            var updateWysiwyg = function () {
                $('#buttonsreply').appendTo('#hdmx_wysiwig-buttons');
                if (!$('#reply_parent').length && $('#togglereply').length) {
                    $('#togglereply').click();
                }
            };
        }
    });
});
