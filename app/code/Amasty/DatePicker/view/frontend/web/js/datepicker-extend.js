/**
 * Amasty DatePicker Extension
 *
 * This module overrides jQuery UI Datepicker's _showDatepicker method
 * to apply accessibility enhancements.
 */
define([
    'jquery',
    'Amasty_DatePicker/js/datepicker-accessibility',
    'domReady!'
], function ($, DatePickerAccessibility) {
    'use strict';

    if (!$.datepicker) {
        return;
    }

    const accessibility = new DatePickerAccessibility();

    const originalConnect = $.datepicker._connectDatepicker;

    $.datepicker._connectDatepicker = function(target, inst) {
        originalConnect.call(this, target, inst);

        if (!$(target).hasClass('am-datepicker-observed')) {
            return;
        }

        requestAnimationFrame(() => accessibility.updateInputLabel($(target)));
    };

    const originalSelectDate = $.datepicker._selectDate;

    $.datepicker._selectDate = function(id, dateStr) {
        const inputElement = typeof id === 'string' ? document.querySelector(id) : (id?.currentTarget || id);

        originalSelectDate.call(this, id, dateStr);

        if (!inputElement || !inputElement.closest || !inputElement.closest('.am-datepicker-observed')) {
            return;
        }

        requestAnimationFrame(() => {
            const $updatedCalendar = $(accessibility.calendarSelector);
            accessibility.makeCalendarAccessible($updatedCalendar, inputElement, true);
        });
    };

    const originalHide = $.datepicker._hideDatepicker;

    $.datepicker._hideDatepicker = function() {
        originalHide.apply(this, arguments);
        accessibility.markClosed();
    };

    const originalShow = $.datepicker._showDatepicker;

    /**
     * Override of jQuery UI Datepicker's _showDatepicker method
     * @param {HTMLElement|Event} datepickerInput - The input element or event object
     */
    $.datepicker._showDatepicker = function(datepickerInput) {
        const actualInput = datepickerInput?.currentTarget || datepickerInput;

        if (datepickerInput?.type === 'focus'
            && !!actualInput?.closest?.('.am-datepicker-observed')
        ) {
            return;
        }

        const skipInitialFocus = !!$.datepicker?._datepickerShowing;

        if (!skipInitialFocus) {
            accessibility.setOpenerElement(document.activeElement);
        }

        originalShow.call(this, datepickerInput);

        if (!actualInput || !actualInput.closest || !actualInput.closest('.am-datepicker-observed')) {
            return;
        }

        accessibility.makeCalendarAccessible($('#ui-datepicker-div'), actualInput, skipInitialFocus);

        $(actualInput).on('click', function () {
            $(this).datepicker('hide');
        });
    };

    $('.am-datepicker-observed').each(function() {
        let inst;

        try {
            inst = $.datepicker._getInst(this);
        } catch (e) {
            return;
        }

        if (inst?.trigger?.length) {
            accessibility.updateInputLabel($(this));
        }
    });

    return accessibility;
});
