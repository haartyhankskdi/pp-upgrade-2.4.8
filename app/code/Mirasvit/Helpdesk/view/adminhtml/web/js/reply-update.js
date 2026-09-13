function replyUpdateSetup($, getEditorFunc) {
    var updateTimer = false;
    if (typeof draftText == 'undefined') {
        draftText = '';
    }
    $('body').on('click', '.page-actions-buttons > div ul > li >span', function (){
        stopDraft();
    });
    $('body').on('click', '.page-actions-buttons > div .action-default', function (){
        stopDraft();
    });
    function stopDraft() {
        if (updateTimer) {
            window.clearInterval(updateTimer);
        }
    }
    // The reply textarea is rendered by the knockout reply-area component under
    // [data-field=helpdesk-reply-field]; the legacy #reply id no longer exists.
    // Fall back to #reply for any theme/version that still renders it.
    function getReplyField() {
        var $field = $('[data-field=helpdesk-reply-field] textarea');
        if (!$field.length) {
            $field = $('#reply');
        }
        return $field;
    }

    getReplyField().val(draftText);
    var origText = getReplyField().val();

    function updateActivity() {
        if (!isAllowDraft) {
            return;
        }

        var text = -1;

        var currentText;
        var editor = getEditorFunc();
        if (editor) {
            currentText = editor.getContent();
        } else {
            currentText = getReplyField().val();
        }
        // Only diff the body when we could actually read it. When the editor is
        // idle and the field is unreadable, currentText is undefined - we must
        // still fire the heartbeat as a presence-only ping (text = -1) so the
        // server expires other viewers' 20s locks; skipping it left the
        // "X has opened this ticket" notice stuck forever (pm#915).
        if (typeof currentText != 'undefined' && currentText != origText) {
            origText = currentText;
            text = origText;
        }
        $.ajax(draftUpdateUrl, {
            method : "post",
            loaderArea: false,
            data : {ticket_id: draftTicketId, text: text},
            dataType: 'json',
            success : function(response) {
                if (typeof response.ajaxExpired != 'undefined' && response.ajaxExpired) {
                    alert($.mage.__('Session has expired. Please copy all unsaved data and reload the page.'));
                    stopDraft();
                    throw new Error('Session expired.');//we need this ot prevent page reload
                    return;
                }
                if (typeof response.error != 'undefined') {
                    alert(response.error);
                    stopDraft();
                    return;
                }
                if (typeof response.form_key != 'undefined') {
                    $('[name="form_key"]').val(response.form_key);
                    FORM_KEY = response.form_key;
                }
                draftText = text;
                if (response.text.indexOf('<head>') == -1) {
                    if ($('main.page-content').length) {
                        $($('main .helpdesk-message')[0]).remove();
                        $('main.page-content').prepend(response.text);
                    } else {
                        $('header').next('.messages').remove();
                        $(response.text).insertAfter('header main');
                    }
                }
                if (response.url) {
                    draftUpdateUrl = response.url;
                }
            },
        });
    }

    if (draftTicketId) {
        updateTimer = window.setInterval(updateActivity, draftDelayPeriod);
    }
}

require([
    'jquery',
    'wysiwygAdapter'
], function ($, tinyMCE) {  //magento >= 2.3.0
    'use strict';
    replyUpdateSetup($, function(){
        return tinyMCE.activeEditor()
    })
});
