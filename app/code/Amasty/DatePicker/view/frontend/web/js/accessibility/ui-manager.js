/**
 * UI Elements Manager
 *
 * Manages UI elements (buttons, labels) for the datepicker calendar
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    /**
     * UI Manager Class
     */
    class UiManager {
        /**
         * Constructor
         * @param {Object} config - Configuration object
         * @param {string} config.calendarSelector - Calendar selector
         * @param {Function} config.replaceSelectsWithLabelCallback - Callback to replace selects
         * @param {Function} config.updateInputLabelCallback - Callback to update input label
         * @param {Function} config.makeCalendarAccessibleCallback - Callback to fully reinitialize calendar accessibility
         */
        constructor(config) {
            this.calendarSelector = config.calendarSelector;
            this.replaceSelectsWithLabelCallback = config.replaceSelectsWithLabelCallback;
            this.updateInputLabelCallback = config.updateInputLabelCallback;
            this.makeCalendarAccessibleCallback = config.makeCalendarAccessibleCallback;
            this.inputElement = null;
            this.originalValue = null;
            this.isOpen = false;
            this.openerElement = null;
        }

        /**
         * Sets the current input element associated with the open calendar
         * @param {HTMLElement} inputElement
         */
        setInputElement(inputElement) {
            if (!this.isOpen) {
                this.originalValue = $(inputElement).val();
                this.isOpen = true;
            }
            this.inputElement = inputElement;
        }

        setOpenerElement(el) {
            this.openerElement = el;
        }

        /**
         * Re-anchors the popup below its input using the calendar's current size.
         * Uses getBoundingClientRect() + scroll instead of $.datepicker._findPos(),
         * which mage/calendar.js patches to skip scroll for .modal-slide inputs.
         * @param {HTMLElement} inputElement
         * @returns {void}
         */
        repositionCalendar(inputElement) {
            let inst;

            try {
                inst = $.datepicker._getInst(inputElement);
            } catch (e) {
                return;
            }

            if (!inst || !inst.dpDiv || !inst.dpDiv.is(':visible')) {
                return;
            }

            const rect = inputElement.getBoundingClientRect();
            const isFixedPosition = inst.dpDiv.css('position') === 'fixed';
            const scrollLeft = isFixedPosition ? 0 : (window.pageXOffset || document.documentElement.scrollLeft);
            const scrollTop = isFixedPosition ? 0 : (window.pageYOffset || document.documentElement.scrollTop);

            inst.dpDiv.css({
                left: (rect.left + scrollLeft) + 'px',
                top: (rect.bottom + scrollTop) + 'px'
            });
        }

        resetOpenState() {
            this.isOpen = false;
            this.originalValue = null;
        }

        markClosed() {
            this.isOpen = false;
        }

        /**
         * Replaces month/year select dropdowns with a text label
         * @param {jQuery} $calendar - The calendar jQuery element
         */
        replaceSelectsWithLabel($calendar) {
            const $monthSelect = $calendar.find('.ui-datepicker-month');
            const $yearSelect = $calendar.find('.ui-datepicker-year');
            const $title = $calendar.find('.ui-datepicker-title');

            if (($monthSelect.length > 0 || $yearSelect.length > 0) && !$title.hasClass('label-replaced')) {
                const monthText = $monthSelect.length > 0 ? $monthSelect.find('option:selected').text() : '';
                const yearText = $yearSelect.length > 0 ? $yearSelect.find('option:selected').text() : '';
                const labelText = monthText && yearText ? `${monthText} ${yearText}` :
                    monthText ? monthText : yearText;

                $monthSelect.remove();
                $yearSelect.remove();

                if (labelText) {
                    const $label = $('<span>')
                        .addClass('ui-datepicker-month-year-label')
                        .text(labelText);

                    $title.empty().append($label).addClass('label-replaced');
                }
            } else if ($title.length > 0 && !$title.hasClass('label-replaced')) {
                $title.addClass('label-replaced');
            }
        }

        /**
         * Sets up navigation buttons (prev/next) with ARIA attributes and event handlers
         * @param {jQuery} $calendar - The calendar jQuery element
         */
        setupNavigationButtons($calendar) {
            const $prevButton = $calendar.find('.ui-datepicker-prev');
            const $nextButton = $calendar.find('.ui-datepicker-next');

            $prevButton.attr({
                'role': 'button',
                'aria-label': $t('Previous Month'),
                'tabindex': '0'
            }).removeAttr('title');

            $nextButton.attr({
                'role': 'button',
                'aria-label': $t('Next Month'),
                'tabindex': '0'
            }).removeAttr('title');

            $calendar.find('.ui-datepicker-prev, .ui-datepicker-next')
                .off('click.update-label')
                .on('click.update-label', () => requestAnimationFrame(() => {
                    this.makeCalendarAccessibleCallback($(this.calendarSelector), this.inputElement, false);
                }));

            this.createActionButtons($calendar);
        }

        /**
         * Builds a "Today" button when showButtonPanel is enabled on the instance.
         * @returns {jQuery|null}
         */
        buildTodayButton() {
            let inst;

            try {
                inst = $.datepicker._getInst(this.inputElement);
            } catch (e) {
                return null;
            }

            if (!inst || !inst.settings || !inst.settings.showButtonPanel) {
                return null;
            }

            const inputElement = this.inputElement,
                label = inst.settings.currentText || $t('Today'),
                $todayButton = $('<button>')
                    .addClass('custom-datepicker-today')
                    .attr({
                        'type': 'button',
                        'role': 'button',
                        'aria-label': label,
                        'tabindex': '0'
                    })
                    .text(label),
                goToToday = (e) => {
                    e.preventDefault();
                    $.datepicker._gotoToday(inputElement);
                    requestAnimationFrame(() => this.makeCalendarAccessibleCallback(
                        $(this.calendarSelector),
                        inputElement,
                        false
                    ));
                };

            $todayButton.on('click', goToToday);

            $todayButton.on('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    goToToday(e);
                }
            });

            return $todayButton;
        }

        /**
         * Creates and sets up custom Today/OK/Cancel buttons for the calendar
         * @param {jQuery} $calendar - The calendar jQuery element
         */
        createActionButtons($calendar) {
            $calendar.find('.ui-datepicker-buttonpane').hide();

            if ($calendar.find('.custom-datepicker-buttonpane').length === 0) {
                const $buttonPane = $('<div>')
                    .addClass('custom-datepicker-buttonpane');

                const $todayButton = this.buildTodayButton();

                const $cancelButton = $('<button>')
                    .addClass('custom-datepicker-cancel')
                    .attr({
                        'type': 'button',
                        'role': 'button',
                        'aria-label': $t('Cancel'),
                        'tabindex': '0'
                    })
                    .text($t('Cancel'));

                const $okButton = $('<button>')
                    .addClass('custom-datepicker-ok')
                    .attr({
                        'type': 'button',
                        'role': 'button',
                        'aria-label': $t('OK'),
                        'tabindex': '0'
                    })
                    .text($t('OK'));

                if ($todayButton) {
                    $buttonPane.append($todayButton);
                }

                $buttonPane.append($cancelButton, $okButton);
                $calendar.append($buttonPane);

                $cancelButton.on('click', (e) => {
                    e.preventDefault();
                    this.handleCancelButton();
                });

                $okButton.on('click', (e) => {
                    e.preventDefault();
                    this.handleOkButton();
                });

                $cancelButton.on('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.handleCancelButton();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this.handleCancelButton();
                    }
                });

                $okButton.on('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.handleOkButton();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this.handleCancelButton();
                    }
                });
            }
        }

        /**
         * Resolves the trigger button or input to return focus to after closing
         * @returns {jQuery} - The element to focus
         */
        resolveFocusTarget() {
            const $opener = this.openerElement ? $(this.openerElement) : null;

            if ($opener?.length && !$opener.is('body') && $opener.is(':visible') && !$opener.prop('disabled')) {
                return $opener;
            }

            const $input = this.inputElement
                ? $(this.inputElement)
                : $('input.hasDatepicker').first();

            if (!$input.length) {
                return $();
            }

            let inst;

            try {
                inst = $.datepicker._getInst($input[0]);
            } catch (e) {
                return $input;
            }

            const $trigger = inst?.trigger;

            if ($trigger?.length && $trigger.is(':visible')) {
                return $trigger;
            }

            return $input;
        }

        /**
         * Focuses the trigger element after the calendar is closed
         */
        focusAfterClose() {
            const [el] = this.resolveFocusTarget();

            if (!el) {
                return;
            }

            if (el.tagName === 'INPUT') {
                //prevent calendar reopen for showOn: 'both' or 'focus'
                $.datepicker._lastInput = el;
            }

            el?.focus();
            $.datepicker._lastInput = null;
        }

        /**
         * Handles cancel button click
         */
        handleCancelButton() {
            const originalValue = this.originalValue;
            const $input = this.inputElement ? $(this.inputElement) : $('input.hasDatepicker').first();

            this.resetOpenState();
            $.datepicker._hideDatepicker();

            if (originalValue !== null) {
                $input.val(originalValue).trigger('change');
            }

            this.focusAfterClose();
        }

        /**
         * Handles OK button click
         */
        handleOkButton() {
            const $input = this.inputElement ? $(this.inputElement) : $('input.hasDatepicker').first();
            const $selected = $(this.calendarSelector).find('td.ui-datepicker-current-day a').first();

            if ($selected.length) $selected.click();

            this.resetOpenState();
            $.datepicker._hideDatepicker();

            const [inputEl] = $input;

            if (inputEl) this.updateInputLabelCallback($input);

            this.focusAfterClose();
        }
    }

    return UiManager;
});

