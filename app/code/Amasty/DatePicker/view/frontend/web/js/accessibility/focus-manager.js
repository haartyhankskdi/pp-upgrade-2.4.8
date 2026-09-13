/**
 * Focus Manager
 *
 * Manages focus behavior for the datepicker calendar
 */
define([
    'jquery',
    'jquery/ui-modules/focusable',
    'jquery/ui-modules/tabbable',
], function ($) {
    'use strict';

    /**
     * Focus Manager Class
     */
    class FocusManager {
        /**
         * Updates the roving tabindex for date navigation.
         * Tabindex is placed on <td> elements (not <a>) so screen readers
         * announce only the focused cell, not the entire dialog.
         * @param {jQuery} $allCells - Collection of all selectable <td> elements
         * @param {number} newIndex - Index of the element to receive tabindex="0"
         */
        updateRovingTabindex($allCells, newIndex) {
            $allCells.attr('tabindex', '-1');

            if (newIndex >= 0 && newIndex < $allCells.length) {
                $allCells.eq(newIndex).attr('tabindex', '0');
            }
        }

        /**
         * Returns true when a <td> is selectable (not disabled and contains a date link)
         * @param {jQuery} $td
         * @returns {boolean}
         */
        isSelectableCell($td) {
            return !$td.hasClass('ui-datepicker-unselectable') && $td.find('a').length > 0;
        }

        /**
         * Returns all selectable date cells (<td>) in the calendar
         * @param {jQuery} $calendar
         * @returns {jQuery}
         */
        getSelectableCells($calendar) {
            return $calendar.find('td').filter((_, td) => this.isSelectableCell($(td)));
        }

        /**
         * Sets up focus handling for the calendar table
         * @param {jQuery} $table - The table jQuery element
         */
        removeTableFocus($table) {
            $table.removeAttr('tabindex').off('focus.table-focus');
        }

        /**
         * Returns focusable elements within the calendar (excluding decorative icons)
         * @param {jQuery} $calendar
         * @returns {jQuery}
         */
        getFocusableElements($calendar) {
            return $calendar
                .find(':tabbable');
        }

        /**
         * Returns true when the given element is a selectable date cell
         * @param {jQuery} $el
         * @returns {boolean}
         */
        isDateCell($el) {
            return $el.is('td') && $el.find('a').length > 0;
        }

        /**
         * Returns true when the given element is outside the calendar
         * @param {jQuery} $calendar
         * @param {jQuery} $el
         * @returns {boolean}
         */
        isOutsideCalendar($calendar, $el) {
            return !$.contains($calendar[0], $el[0]);
        }

        /**
         * Resolves the element that should receive focus on Shift+Tab traversal.
         * @param {jQuery} $calendar
         * @param {jQuery} $current - Currently focused element
         * @param {jQuery} $first - First focusable element
         * @param {jQuery} $last - Last focusable element
         * @returns {jQuery|null} Target element, or null when no redirect is needed
         */
        resolveBackwardFocusTarget($calendar, $current, $first, $last) {
            if (this.isOutsideCalendar($calendar, $current) || $current.is($first)) {
                return $last;
            }

            if (this.isDateCell($current)) {
                const $nextBtn = $calendar.find('.ui-datepicker-next');

                return $nextBtn.length ? $nextBtn : $first;
            }

            return null;
        }

        /**
         * Resolves the element that should receive focus on forward Tab traversal.
         * @param {jQuery} $calendar
         * @param {jQuery} $current - Currently focused element
         * @param {jQuery} $first - First focusable element
         * @param {jQuery} $last - Last focusable element
         * @returns {jQuery|null} Target element, or null when no redirect is needed
         */
        resolveForwardFocusTarget($calendar, $current, $first, $last) {
            if (this.isOutsideCalendar($calendar, $current) || $current.is($last)) {
                return $first;
            }

            if (this.isDateCell($current)) {
                const timePickerInput = $calendar.find('.ui-timepicker-select:visible');

                if (timePickerInput.length) {
                    return timePickerInput.first();
                }

                const $todayBtn = $calendar.find('.custom-datepicker-today');

                if ($todayBtn.length) {
                    return $todayBtn;
                }

                const $cancelBtn = $calendar.find('.custom-datepicker-cancel');

                return $cancelBtn.length ? $cancelBtn : $last;
            }

            return null;
        }

        /**
         * Sets up a focus trap to keep keyboard focus within the calendar dialog
         * @param {jQuery} $calendar - The calendar jQuery element
         */
        setupFocusTrap($calendar) {
            $calendar.off('keydown.focus-trap').on('keydown.focus-trap', (e) => {
                if (e.key !== 'Tab') return;

                const $focusable = this.getFocusableElements($calendar);

                if ($focusable.length === 0) return;

                const $first = $focusable.first();
                const $last = $focusable.last();
                const $current = $(document.activeElement);

                const $target = e.shiftKey
                    ? this.resolveBackwardFocusTarget($calendar, $current, $first, $last)
                    : this.resolveForwardFocusTarget($calendar, $current, $first, $last);

                if (!$target) return;

                e.preventDefault();
                $target.focus();
            });
        }

        /**
         * Sets the initial focus to the appropriate date cell (<td>) when the calendar opens.
         * Focusing <td role="gridcell"> instead of the inner <a> prevents screen readers
         * from reading the entire dialog content on open.
         * @param {jQuery} $calendar - The calendar jQuery element
         */
        setInitialFocus($calendar) {
            const $selectableCells = this.getSelectableCells($calendar);
            const [selectedCell] = $selectableCells.filter('.ui-datepicker-current-day');
            const [todayCell] = $selectableCells.filter('.ui-datepicker-today');
            const [firstCell] = $selectableCells;

            // .ui-datepicker-current-day/.ui-datepicker-today may be assigned to a disabled
            // (blocked) day by jQuery UI regardless of selectability, so both are filtered
            // through $selectableCells above - if the selected/today cell is blocked, focus
            // falls through to the first available day instead.
            const toFocus = selectedCell ?? todayCell ?? firstCell;

            toFocus && $(toFocus).attr('tabindex', '0').focus();
        }

        /**
         * Focuses on a specific day number in the calendar
         * @param {jQuery} $calendar - The calendar jQuery element
         * @param {number} targetDay - The day number to focus (1-31)
         */
        focusOnDay($calendar, targetDay) {
            const $selectableCells = this.getSelectableCells($calendar);

            if ($selectableCells.length === 0) {
                $calendar.get(0)?.focus();

                return;
            }

            const day = String(targetDay);
            const [matchedLink] = $selectableCells.find(`a:contains(${day})`).filter((_, a) => $(a).text().trim() === day);
            const $targetCell = matchedLink ? $(matchedLink).closest('td') : $selectableCells.last();
            const newIndex = $selectableCells.index($targetCell);

            if (newIndex >= 0) {
                this.updateRovingTabindex($selectableCells, newIndex);
                $targetCell.focus();
            }
        }
    }

    return FocusManager;
});

