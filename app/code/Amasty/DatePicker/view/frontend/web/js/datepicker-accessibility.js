/**
 * Amasty DatePicker Accessibility Class
 *
 * This module provides accessibility features for jQuery UI Datepicker
 * to comply with WCAG 2.1 standards and improve keyboard navigation.
 *
 * Features:
 * - ARIA attributes for screen reader support
 * - Full keyboard navigation (Arrow keys, Home, End, PageUp, PageDown)
 * - Focus trap within the calendar dialog
 * - Roving tabindex for grid navigation
 * - Custom OK/Cancel buttons
 * - Accessible labels and announcements
 *
 * Keyboard shortcuts:
 * - Arrow keys: Navigate between dates
 * - Home/End: Jump to first/last day of week
 * - PageUp/PageDown: Navigate to previous/next month
 * - Shift+PageUp/PageDown: Navigate to previous/next year
 * - Enter/Space: Select date or activate button
 * - Tab: Navigate between focusable elements
 * - Esc: Close calendar
 */
define([
    'jquery',
    'Amasty_DatePicker/js/accessibility/aria-manager',
    'Amasty_DatePicker/js/accessibility/focus-manager',
    'Amasty_DatePicker/js/accessibility/keyboard-handler',
    'Amasty_DatePicker/js/accessibility/ui-manager'
], function ($, AriaManager, FocusManager, KeyboardHandler, UiManager) {
    'use strict';

    /**
     * DatePicker Accessibility Class
     */
    class DatePickerAccessibility {
        /**
         * Constructor
         */
        constructor() {
            this.calendarSelector = '#ui-datepicker-div';
            this.rowLength = 7;

            this.ariaManager = new AriaManager();
            this.focusManager = new FocusManager();

            this.keyboardHandler = new KeyboardHandler({
                calendarSelector: this.calendarSelector,
                rowLength: this.rowLength,
                focusManager: this.focusManager,
                makeCalendarAccessible: this.makeCalendarAccessible.bind(this),
                focusOnDay: this.focusOnDay.bind(this),
                focusAfterClose: this.focusAfterClose.bind(this),
                handleCancelButton: () => this.uiManager.handleCancelButton(),
            });

            this.uiManager = new UiManager({
                calendarSelector: this.calendarSelector,
                replaceSelectsWithLabelCallback: this.replaceSelectsWithLabel.bind(this),
                updateInputLabelCallback: this.updateInputLabel.bind(this),
                makeCalendarAccessibleCallback: this.makeCalendarAccessible.bind(this)
            });
        }

        /**
         * Replaces month/year select dropdowns with a text label
         * @param {jQuery} $calendar - The calendar jQuery element
         */
        replaceSelectsWithLabel($calendar) {
            this.uiManager.replaceSelectsWithLabel($calendar);
        }

        /**
         * Updates the ARIA label of the input field with the selected date
         * @param {jQuery} $input - The input jQuery element
         */
        updateInputLabel($input) {
            this.ariaManager.updateInputLabel($input);
        }

        /**
         * Focuses on a specific day number in the calendar
         * @param {jQuery} $calendar - The calendar jQuery element
         * @param {number} targetDay - The day number to focus (1-31)
         */
        focusOnDay($calendar, targetDay) {
            this.focusManager.focusOnDay($calendar, targetDay);
        }

        focusAfterClose() {
            this.uiManager.focusAfterClose();
        }

        markClosed() {
            this.uiManager.markClosed();
        }

        setOpenerElement(el) {
            this.uiManager.setOpenerElement(el);
        }

        /**
         * Main function to make the calendar accessible with ARIA attributes and keyboard navigation
         * @param {jQuery} $calendar - The calendar jQuery element
         * @param {HTMLElement} inputElement - The input element associated with the calendar
         * @param {boolean} [skipInitialFocus=false] - Whether to skip setting initial focus
         */
        makeCalendarAccessible($calendar, inputElement, skipInitialFocus) {
            $calendar.addClass('amasty-custom-datepicker');
            this.uiManager.setInputElement(inputElement);
            this.ariaManager.setAriaAttributes($calendar);
            this.replaceSelectsWithLabel($calendar);

            const {$table, $title} = this.ariaManager.setupTableGrid($calendar);

            this.uiManager.setupNavigationButtons($calendar);
            this.ariaManager.setupTimeLabels($calendar);
            this.ariaManager.setupTableStructure($table);
            this.ariaManager.setupDateCells($table, $title);
            this.focusManager.removeTableFocus($table);
            this.ariaManager.updateInputLabel($(inputElement));

            this.keyboardHandler.setupKeyboardNavigation($calendar, $table, inputElement);
            this.focusManager.setupFocusTrap($calendar);
            this.uiManager.repositionCalendar(inputElement);

            if (!skipInitialFocus) {
                requestAnimationFrame(() => this.focusManager.setInitialFocus($calendar));
            }
        }
    }

    return DatePickerAccessibility;
});

