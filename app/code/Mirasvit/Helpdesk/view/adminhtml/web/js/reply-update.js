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
    $('#reply').val(draftText);
    var origText = $('#reply').val();

    function updateActivity() {
        if (!isAllowDraft) {
            return;
        }

        var text = -1;

        var currentText = '';
        var editor = getEditorFunc();
        if(editor) {
            currentText = editor.getContent();
        } else {
            currentText = $('#reply').val();
        }
        if (typeof currentText == 'undefined') {
            return;
        }
        if (currentText != origText) {
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
                    if ($('main').length) {
                        $($('main .helpdesk-message')[0]).remove();
                        $('main').prepend(response.text);
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
    replyUpdateSetup($, function(){ return tinyMCE.activeEditor()})
}, function (err) { //magento < 2.3.0
    require([
        'jquery',
        'tinymce'
    ], function ($) {
        'use strict';
        replyUpdateSetup($, function(){ return tinyMCE.activeEditor})
        tinyMCE.onAddEditor.add(function(obj, editor) {
            editor.onPostRender.add(function(ed, cm) {
                if (draftText && draftText !== -1) {
                    ed.setContent(draftText);
                }
            });
        });
    });
});