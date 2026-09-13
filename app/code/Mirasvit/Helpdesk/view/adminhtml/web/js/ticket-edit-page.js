define([
    "jquery",
    "underscore",
    "uiCollection",
    "Magento_Ui/js/lib/spinner",
    "Magento_Ui/js/modal/modal",
    "domReady!",
    "form"
], function ($, _, Collection, loader, modal) {
    "use strict";

    return Collection.extend({
        initialize: function () {
            this._super().hideLoader();

            $(document).ready(function () {
                let buttons = 0;
                let currentFiles = [];

                if (!$('#file-too-large-modal').length) {
                    $('body').append('<div id="file-too-large-modal"><div class="modal-content"></div></div>');
                }

                const modalOptions = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: $.mage.__('Upload Too Large'),
                    buttons: [{
                        text: $.mage.__('OK'),
                        class: 'action-primary',
                        click: function () {
                            this.closeModal();
                        }
                    }]
                };

                const popup = modal(modalOptions, $('#file-too-large-modal'));

                $(document).on('change', '#hdmx_attachment_wrap input[type="file"]', function(e) {
                    const $input = $(this);
                    const inputFiles = e.target.files;
                    const maxAttachmentSizeText = document.getElementById('max-attachment-size')?.value || '';
                    const match = maxAttachmentSizeText.match(/(\d+(\.\d+)?)/);
                    const maxSizeMb = match ? parseFloat(match[1]) : 2;
                    const maxTotalSize = maxSizeMb * 1024 * 1024;

                    const tooLargeFiles = [];

                    for (let i = 0; i < inputFiles.length; i++) {
                        const file = inputFiles[i];

                        if (currentFiles.some(f => f.name === file.name && f.size === file.size)) {
                            continue;
                        }

                        if (file.size > maxTotalSize) {
                            tooLargeFiles.push(file.name);
                            const labelSelector = `.MultiFile-label:has(.MultiFile-title:contains("${file.name}"))`;
                            const $label = $(labelSelector);
                            $label.find('input[type="file"]').remove();
                            $label.remove();

                            continue;
                        }
                        currentFiles.push(file);
                    }
                    let uploadedFilesSize = 0;
                    const filesToKeep = [];

                    currentFiles.forEach(file => {
                        uploadedFilesSize += file.size;

                        if (uploadedFilesSize < maxTotalSize) {
                            filesToKeep.push(file);
                        } else {
                            const labelSelector = `.MultiFile-label:has(.MultiFile-title:contains("${file.name}"))`;
                            const $label = $(labelSelector);
                            $label.find('input[type="file"]').remove();
                            $label.remove();
                            tooLargeFiles.push(file.name);
                        }
                    });

                    currentFiles = filesToKeep;

                    if (tooLargeFiles.length) {
                        $input.val('');

                        $('#file-too-large-modal .modal-content').text(
                            'Some files exceed the allowed size limits and were removed: ' + tooLargeFiles.join(', ')
                        );
                        $('#file-too-large-modal').modal('openModal');
                    }
                });

                function bindRemoveHandler(el) {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        const $labelDiv = $(this).closest('.MultiFile-label');
                        const filename = $labelDiv.find('.MultiFile-title').text().trim();

                        currentFiles = currentFiles.filter(file => file.name !== filename);
                        $labelDiv.remove();
                    });
                }

                const observer = new MutationObserver(mutations => {
                    mutations.forEach(mutation => {
                        mutation.addedNodes.forEach(node => {
                            if (node.nodeType === 1) {
                                if (node.matches('.MultiFile-remove')) {
                                    bindRemoveHandler(node);
                                }
                                node.querySelectorAll('.MultiFile-remove').forEach(bindRemoveHandler);
                            }
                        });
                    });
                });

                observer.observe(document.body, { childList: true, subtree: true });
                document.querySelectorAll('.MultiFile-remove').forEach(bindRemoveHandler);

                function bindSubmitHandler(scopeSelector) {
                    const $container = $(scopeSelector + ' [data-save-target]');
                    if (!$container.length) return;

                    const el = $container[0];
                    const $form = $($(el).data('save-target'));

                    $form.on('beforeSubmit', function (e) {
                        if (buttons) {
                            e.preventDefault();
                            return;
                        }

                        if (currentFiles.length === 0) {
                            return;
                        }

                        e.preventDefault();
                        buttons = 1;

                        const form = this;
                        $('#hdmx_attachment_wrap input[type="file"]').val('');

                        const replyInput = $(form).find('textarea[name="reply"]');
                        if (typeof tinyMCE != 'undefined' && tinyMCE.activeEditor != null &&
                            (
                                document.getElementsByClassName('tox-tinymce').length ||
                                document.getElementsByClassName('tox-hugerte').length
                            )
                        ) {
                            tinyMCE.triggerSave();
                        }

                        if (replyInput.length && !replyInput.val().trim()) {
                            replyInput.val('[Attachments]');
                        }

                        const formData = new FormData(form);
                        currentFiles.forEach(function(file) {
                            if (file && file.name) {
                                formData.append('attachments[]', file);
                            }
                        });

                        loader.show();

                        $.ajax({
                            url: $(form).attr('action'),
                            method: $(form).attr('method') || 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                loader.hide();
                                buttons = 0;
                                if (response && response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    location.reload();
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Upload failed:', error);
                                loader.hide();
                                buttons = 0;
                            }
                        });
                    });
                }

                bindSubmitHandler('.helpdesk-ticket-edit');
                bindSubmitHandler('.helpdesk-ticket-add');
            });

            return this;
        },

        hideLoader: function () {
            loader.hide();
            setInterval(loader.hide, 3000);
            $('.admin__data-grid-loading-mask').hide();
            return this;
        },

        showLoader: function () {
            loader.show();
        }
    });
});
