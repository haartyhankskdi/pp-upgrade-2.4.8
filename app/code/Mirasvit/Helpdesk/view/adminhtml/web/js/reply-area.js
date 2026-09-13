define([
    'underscore',
    'ko',
    'uiComponent',
    'jquery',
    'Mirasvit_Helpdesk/js/lib/jquery.MultiFile'
], function (_, ko, Component, $) {
    'use strict';

    /*!
     * ----------------------------------------------------------------------------
     * "THE BEER-WARE LICENSE" (Revision 42):
     * <jevin9@gmail.com> wrote this file. As long as you retain this notice you
     * can do whatever you want with this stuff. If we meet some day, and you think
     * this stuff is worth it, you can buy me a beer in return. Jevin O. Sewaruth
     * ----------------------------------------------------------------------------
     *
     * Autogrow Textarea Plugin Version v3.0
     * http://www.technoreply.com/autogrow-textarea-plugin-3-0
     *
     * THIS PLUGIN IS DELIVERD ON A PAY WHAT YOU WHANT BASIS. IF THE PLUGIN WAS USEFUL TO YOU, PLEASE CONSIDER BUYING THE PLUGIN HERE :
     * https://sites.fastspring.com/technoreply/instant/autogrowtextareaplugin
     *
     * Date: October 15, 2012
     */

    $.fn.autoGrow = function(options) {
        return this.each(function() {
            var settings = jQuery.extend({
                extraLine: true
            }, options);

            var createMirror = function(textarea) {
                jQuery(textarea).after('<div class="autogrow-textarea-mirror"></div>');
                return jQuery(textarea).next('.autogrow-textarea-mirror')[0];
            };

            var sendContentToMirror = function (textarea) {
                mirror.innerHTML = String(textarea.value)
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/ /g, '&nbsp;')
                        .replace(/\n/g, '<br />') +
                    (settings.extraLine? '.<br/>.' : '')
                ;
                if (jQuery(mirror).height() < 150) {
                    return;
                }
                if (jQuery(textarea).height() != jQuery(mirror).height())
                    jQuery(textarea).height(jQuery(mirror).height());
            };

            var growTextarea = function () {
                sendContentToMirror(this);
            };

            // Create a mirror
            var mirror = createMirror(this);

            // Style the mirror
            mirror.style.display = 'none';
            //mirror.style.wordWrap = 'break-word';
            mirror.style.whiteSpace = 'normal';
            mirror.style.padding = jQuery(this).css('padding');
            mirror.style.width = jQuery(this).css('width');
            mirror.style.fontFamily = jQuery(this).css('font-family');
            mirror.style.fontSize = jQuery(this).css('font-size');
            mirror.style.lineHeight = jQuery(this).css('line-height');

            // Style the textarea
            this.style.overflow = "hidden";
            this.style.minHeight = this.rows+"em";

            // Bind the textarea's event
            this.onkeyup = growTextarea;

            // Fire the event for text already present
            sendContentToMirror(this);

        });
    };

    let replyText     = '';
    let replySelector = '[data-field=helpdesk-reply-field] textarea';

    return Component.extend({
        replyType: ko.observable('public'),
        defaults:  {
            areaValueUpdatePeriod: 300,
            template: 'Mirasvit_Helpdesk/reply-area'
        },

        initialize: function () {
            this._super();
            this._bind();


            return this;
        },
        _bind: function () {
            let defaultClasses = '';

            $('body').on('mst-hdmx-switch-reply-type', function (e, v) {
                let $replyArea = $(replySelector);

                if (!defaultClasses) {
                    defaultClasses = $replyArea.attr('class');
                }

                $replyArea.attr('class', defaultClasses + ' ' + v);
            });

            $('body').on('mst-hdmx-reply-change', function (e) {
                let $replyArea = $(replySelector);

                let text = '';
                if (typeof tinyMCE != 'undefined' && tinyMCE.activeEditor != null &&
                    (document.getElementsByClassName('mceEditor').length || // old magento
                        document.getElementsByClassName('mce-tinymce').length || // m2.3
                        document.getElementsByClassName('tox-tinymce').length ||//m2.4
                        document.getElementsByClassName('tox-hugerte').length //m2.4
                    )
                ) {
                    text = tinyMCE.activeEditor.getContent();
                } else {
                    text = $replyArea.val();
                }

                $('body').trigger('mst-hdmx-reply-content-change', text);
            });

            $('body').on('mst-hdmx-reply-add-text', function (e, body) {
                let $replyArea = $(replySelector);

                if (typeof tinyMCE != 'undefined' && tinyMCE.activeEditor != null &&
                    (document.getElementsByClassName('mceEditor').length || // old magento
                        document.getElementsByClassName('mce-tinymce').length || // m2.3
                        document.getElementsByClassName('tox-tinymce').length ||//m2.4
                        document.getElementsByClassName('tox-hugerte').length //m2.4
                    )
                ) {
                    tinyMCE.activeEditor.execCommand('mceInsertContent', false, body);
                } else {
                    let val = $replyArea.val();

                    if (val != '') {
                        val = val + "\n"
                    }

                    $replyArea.val(val + body);

                    replyText = $replyArea.val();
                }

                $replyArea.autoGrow();
            });

            $('body').on('mst-hdmx-reply-replace-trigger', function (e, trigger, body) {
                var $replyArea = $(replySelector);

                if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor != null &&
                    (document.getElementsByClassName('mceEditor').length || // old magento
                        document.getElementsByClassName('mce-tinymce').length || // m2.3
                        document.getElementsByClassName('tox-tinymce').length || //m2.4
                        document.getElementsByClassName('tox-hugerte').length //m2.4
                    )
                ) {
                    var content = tinyMCE.activeEditor.getContent();
                    tinyMCE.activeEditor.setContent(content.replace(trigger, body));
                } else {
                    var val = $replyArea.val();
                    $replyArea.val(val.replace(trigger, body));
                    $replyArea.autoGrow();
                }
            });

            $('body').on('mst-hdmx-reply-preview-text', function (e, body, isShow) {
                let $replyArea = $(replySelector);

                if (isShow) {
                    let currentReply = '';

                    if (typeof tinyMCE != 'undefined' && tinyMCE.activeEditor != null &&
                        (document.getElementsByClassName('mceEditor').length || // old magento
                            document.getElementsByClassName('mce-tinymce').length || // m2.3
                            document.getElementsByClassName('tox-tinymce').length ||//m2.4
                            document.getElementsByClassName('tox-hugerte').length //m2.4
                        )
                    ) {
                        currentReply = tinyMCE.activeEditor.getContent();

                        $('.hdmx__reply .admin__control-wysiwig .mce-edit-area').append('<div class="wysiwig-preview"></div>');
                    } else {
                        $replyArea.addClass('preview');

                        currentReply = $replyArea.val();
                    }

                    $('body').trigger('mst-hdmx-reply-add-text', body);

                    replyText = currentReply;
                } else {
                    if (typeof tinyMCE != 'undefined' && tinyMCE.activeEditor != null &&
                        (document.getElementsByClassName('mceEditor').length || // old magento
                            document.getElementsByClassName('mce-tinymce').length || // m2.3
                            document.getElementsByClassName('tox-tinymce').length ||//m2.4
                            document.getElementsByClassName('tox-hugerte').length //m2.4
                        )
                    ) {
                        $('.wysiwig-preview').remove();

                        tinyMCE.activeEditor.undoManager.undo();
                    } else {
                        $replyArea.val('');
                        $replyArea.removeClass('preview');

                        $('body').trigger('mst-hdmx-reply-add-text', replyText);
                    }
                }
            });

            window.setTimeout(this.updateAreaValue, this.areaValueUpdatePeriod, this);
            window.setTimeout(this.waitEditorInit.bind(this), this.areaValueUpdatePeriod);
        },

        updateAreaValue: function (self) {
            if (!$(replySelector).length) {
                window.setTimeout(self.updateAreaValue, self.areaValueUpdatePeriod, self);
            }

            if (draftText) {
                $(replySelector).val(draftText);
            }
        },

        waitEditorInit: function () {
            let $editorSelector = $(replySelector);

            if ($editorSelector.length) {

                if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor != null) {
                    try {

                        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor != null) {
                            let content = tinyMCE.activeEditor.getContent();
                            $('body').trigger('mst-hdmx-reply-content-change', content);

                            tinyMCE.activeEditor.on('keyup', function () {
                                $('body').trigger('mst-hdmx-reply-content-change', tinyMCE.activeEditor.getContent());
                            });
                        }

                    } catch (e) {
                        window.setTimeout(this.waitEditorInit.bind(this), this.areaValueUpdatePeriod);
                    }
                } else {

                    if (typeof wysiwygreply !== 'undefined' && wysiwygreply && typeof wysiwygreply.toggle === 'function') {
                        wysiwygreply.toggle();
                        window.setTimeout(this.waitEditorInit.bind(this), this.areaValueUpdatePeriod);
                    }

                    $('body').trigger('mst-hdmx-reply-content-change', $(replySelector).val());
                    $editorSelector.on('keyup', function () {
                        $('body').trigger('mst-hdmx-reply-content-change', $editorSelector.val());
                    });
                }
            }
        }
    });
});
