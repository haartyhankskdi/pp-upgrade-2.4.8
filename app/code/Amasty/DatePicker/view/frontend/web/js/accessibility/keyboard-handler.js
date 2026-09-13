/**
 * Keyboard Navigation Handler
 *
 * Handles keyboard navigation for the datepicker calendar
 */
define([
    'jquery'
], function ($) {
    'use strict';

    /**
     * Keyboard Handler Class
     */
    class KeyboardHandler {
        /**
         * Constructor
         * @param {Object} config - Configuration object
         * @param {string} config.calendarSelector - Calendar selector
         * @param {number} config.rowLength - Number of columns in grid
         * @param {FocusManager} config.focusManager - Focus manager instance
         * @param {Function} config.makeCalendarAccessible - Callback to reinitialize calendar
         * @param {Function} config.focusOnDay - Callback to focus on specific day
         * @param {Function} config.focusAfterClose - Callback to handle focus after calendar close
         */
        constructor(config) {
            this.calendarSelector = config.calendarSelector;
            this.rowLength = config.rowLength;
            this.focusManager = config.focusManager;
            this.makeCalendarAccessible = config.makeCalendarAccessible;
            this.focusOnDay = config.focusOnDay;
            this.focusAfterClose = config.focusAfterClose;
            this.handleCancelButton = config.handleCancelButton;
        }

        /**
         * Clicks a prev/next navigation button, restores focus to the same day,
         * then returns focus to the nav button.
         * @param {jQuery} $btn - The nav button element
         * @param {HTMLElement} inputElement
         */
        activateNavButton($btn, inputElement) {
            const isPrev = $btn.hasClass('ui-datepicker-prev');
            const $activeTd = this.focusManager.getSelectableCells($(this.calendarSelector))
                .filter((_, td) => $(td).attr('tabindex') === '0')
                .first();
            const currentDay = parseInt($activeTd.find('a').text()) || 1;

            this.navigateByMonthCount(inputElement, isPrev ? -1 : +1, currentDay, ($updated) => {
                const $activeCell = this.focusManager.getSelectableCells($updated)
                    .filter((_, td) => $(td).attr('tabindex') === '0')
                    .first();

                $activeCell.addClass('am-datepicker-cell-active');

                const [btnEl] = isPrev
                    ? $updated.find('.ui-datepicker-prev')
                    : $updated.find('.ui-datepicker-next');

                btnEl?.focus();

                $updated.one('focusin.cell-active', 'td', () => {
                    $updated.find('.am-datepicker-cell-active').removeClass('am-datepicker-cell-active');
                });
            });
        }

        /**
         * @param {HTMLElement} inputElement
         * @param {number} year
         * @param {number} month - 0-indexed month
         * @returns {boolean}
         */
        monthHasSelectableDay(inputElement, year, month) {
            let inst;

            try {
                inst = $.datepicker._getInst(inputElement);
            } catch (e) {
                return true;
            }

            const beforeShowDay = inst?.settings?.beforeShowDay;

            if (typeof beforeShowDay !== 'function') {
                return true;
            }

            const daysInMonth = new Date(year, month + 1, 0).getDate();

            for (let day = 1; day <= daysInMonth; day++) {
                const result = beforeShowDay(new Date(year, month, day));

                if (result && result[0]) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Navigates to an adjacent month and focuses the boundary cell.
         * Used by ArrowLeft (prev month, last cell) and ArrowRight (next month, first cell).
         * @param {jQuery} $fromCell - Cell focus is moving away from
         * @param {HTMLElement} inputElement
         * @param {number} offset - Month offset: -1 for previous, +1 for next
         * @param {'first'|'last'} edge - Which boundary cell to focus after navigation
         */
        navigateToMonth($fromCell, inputElement, offset, edge) {
            let inst;

            try {
                inst = $.datepicker._getInst(inputElement);
            } catch (e) {
                inst = null;
            }

            if (inst) {
                const totalMonths = inst.drawYear * 12 + inst.drawMonth + offset;
                const targetMonth = ((totalMonths % 12) + 12) % 12;
                const targetYear = Math.floor(totalMonths / 12);

                if (!this.monthHasSelectableDay(inputElement, targetYear, targetMonth)) {
                    $fromCell.get(0)?.focus();

                    return;
                }
            }

            $.datepicker._adjustDate($(inputElement), offset, 'M');

            requestAnimationFrame(() => {
                const $updatedCalendar = $(this.calendarSelector);

                this.makeCalendarAccessible($updatedCalendar, inputElement, true);

                const $cells = this.focusManager.getSelectableCells($updatedCalendar);

                if (!$cells.length) {
                    $.datepicker._adjustDate($(inputElement), -offset, 'M');

                    requestAnimationFrame(() => {
                        const $revertedCalendar = $(this.calendarSelector);

                        this.makeCalendarAccessible($revertedCalendar, inputElement, true);
                        this.focusOnDay($revertedCalendar, parseInt($fromCell.find('a').text(), 10));
                    });

                    return;
                }

                const newIndex = edge === 'first' ? 0 : $cells.length - 1;
                const [cellEl] = edge === 'first' ? $cells : [...$cells].reverse();

                this.focusManager.updateRovingTabindex($cells, newIndex);
                cellEl?.focus();
            });
        }

        /**
         * Navigates forward or backward by months/years and restores focus to the same day.
         * Used by PageUp (back) and PageDown (forward).
         * @param {HTMLElement} inputElement
         * @param {number} offset - Signed month count (negative = back, positive = forward)
         * @param {number} targetDay - Day number to restore focus to after navigation
         * @param {Function} [afterNavigate] - Optional callback run after focusOnDay
         */
        navigateByMonthCount(inputElement, offset, targetDay, afterNavigate) {
            $.datepicker._adjustDate($(inputElement), offset, 'M');

            requestAnimationFrame(() => {
                const $updated = $(this.calendarSelector);

                this.makeCalendarAccessible($updated, inputElement, true);
                this.focusOnDay($updated, targetDay);
                afterNavigate?.($updated);
            });
        }

        /**
         * Finds the nearest selectable <td> sibling in the given direction
         * by walking through all tbody cells in DOM order.
         * @param {jQuery} $calendar
         * @param {jQuery} $fromCell - Currently focused cell
         * @param {'left'|'right'} direction
         * @returns {jQuery|null}
         */
        findAdjacentCell($calendar, $fromCell, direction) {
            const $allTd = $calendar.find('tbody td');
            const domIndex = $allTd.index($fromCell);
            const step = direction === 'left' ? -1 : 1;
            const limit = direction === 'left' ? -1 : $allTd.length;

            for (let i = domIndex + step; i !== limit; i += step) {
                const $td = $allTd.eq(i);

                if (this.focusManager.isSelectableCell($td)) {
                    return $td;
                }
            }

            return null;
        }

        /**
         * Moves focus to an adjacent cell in the same column, one row up or down.
         * @param {jQuery} $focused - Currently focused cell
         * @param {jQuery} $allCells - All selectable cells
         * @param {'prev'|'next'} rowDirection
         */
        navigateVertical($focused, $allCells, rowDirection) {
            const $currentRow = $focused.closest('tr');
            const colIndex = $currentRow.find('td').index($focused);
            const $target = $currentRow[rowDirection]('tr').find('td').eq(colIndex);
            const [targetEl] = $target;

            if (targetEl && this.focusManager.isSelectableCell($target)) {
                this.focusManager.updateRovingTabindex($allCells, $allCells.index($target));
                targetEl.focus();
            }
        }

        /**
         * Moves focus to the nearest selectable cell left or right,
         * crossing into the adjacent month when at the boundary.
         * @param {jQuery} $focused - Currently focused cell
         * @param {jQuery} $calendar
         * @param {jQuery} $allCells
         * @param {HTMLElement} inputElement
         * @param {'left'|'right'} direction
         */
        navigateHorizontal($focused, $calendar, $allCells, inputElement, direction) {
            const $target = this.findAdjacentCell($calendar, $focused, direction);

            if ($target) {
                this.focusManager.updateRovingTabindex($allCells, $allCells.index($target));
                $target.focus();
            } else {
                this.navigateToMonth($focused, inputElement, direction === 'left' ? -1 : +1, direction === 'left' ? 'last' : 'first');
            }
        }

        /**
         * Handles arrow key navigation within the calendar grid
         * @param {KeyboardEvent} e - The keyboard event
         * @param {jQuery} $calendar - The calendar jQuery element
         * @param {HTMLElement} inputElement - The input element associated with the calendar
         * @param {jQuery} $allCells - Collection of all selectable <td> elements
         * @param {number} currentIndex - Current focused cell index
         * @returns {{handled: boolean}}
         */
        handleArrowNavigation(e, $calendar, inputElement, $allCells, currentIndex) {
            const $focused = $allCells.eq(currentIndex);

            switch (e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    this.navigateHorizontal($focused, $calendar, $allCells, inputElement, 'left');
                    return {handled: true};

                case 'ArrowRight':
                    e.preventDefault();
                    this.navigateHorizontal($focused, $calendar, $allCells, inputElement, 'right');
                    return {handled: true};

                case 'ArrowUp':
                    e.preventDefault();
                    this.navigateVertical($focused, $allCells, 'prev');
                    return {handled: true};

                case 'ArrowDown':
                    e.preventDefault();
                    this.navigateVertical($focused, $allCells, 'next');
                    return {handled: true};

                default:
                    return {handled: false};
            }
        }

        /**
         * Handles grid navigation keys (Home/End/PageUp/PageDown/Arrows) for a focused date cell.
         * @param {KeyboardEvent} e
         * @param {jQuery} $calendar
         * @param {HTMLElement} inputElement
         * @returns {false|undefined}
         */
        handleGridNavigation(e, $calendar, inputElement) {
            const $focused = $(document.activeElement);
            const $activeTd = $focused.is('td') ? $focused : $focused.closest('td');

            if (!$activeTd.length || !$.contains($calendar[0], $activeTd[0])) return;

            e.preventDefault();

            const $allCells = this.focusManager.getSelectableCells($calendar);

            if (!$allCells.length) return false;

            let currentIndex = $allCells.index($activeTd);

            if (currentIndex === -1) currentIndex = 0;

            switch (e.key) {
                case 'Home':
                case 'End': {
                    const $rowCells = $activeTd.closest('tr').find('td').filter((_, td) => this.focusManager.isSelectableCell($(td)));
                    const [$target] = e.key === 'Home' ? $rowCells : [...$rowCells].reverse();
                    const newIndex = $allCells.index($target);

                    if (newIndex >= 0) {
                        this.focusManager.updateRovingTabindex($allCells, newIndex);
                        $target?.focus();
                    }

                    return false;
                }

                case 'PageUp':
                case 'PageDown': {
                    const currentDay = parseInt($activeTd.find('a').text());

                    if (isNaN(currentDay)) return false;

                    const sign = e.key === 'PageUp' ? -1 : +1;

                    this.navigateByMonthCount(inputElement, sign * (e.shiftKey ? 12 : 1), currentDay);
                    return false;
                }

                case 'ArrowLeft':
                case 'ArrowUp':
                case 'ArrowRight':
                case 'ArrowDown':
                    this.handleArrowNavigation(e, $calendar, inputElement, $allCells, currentIndex);
                    return false;
            }
        }

        /**
         * Sets up comprehensive keyboard navigation for the calendar
         * @param {jQuery} $calendar - The calendar jQuery element
         * @param {jQuery} $table - The table jQuery element
         * @param {HTMLElement} inputElement - The input element associated with the calendar
         */
        setupKeyboardNavigation($calendar, $table, inputElement) {
            $calendar.off('keydown.datepicker-navigation').off('keydown.focus-trap');

            $calendar.on('keydown.datepicker-navigation', (e) => {
                const $focused = $(document.activeElement);

                switch (e.key) {
                    case 'Escape': {
                        if ($focused.is('option')) {
                            //time element option
                            return true;
                        }
                        e.preventDefault();
                        this.handleCancelButton();
                        return false;
                    }

                    case 'Tab': {
                        const $focusable = this.focusManager.getFocusableElements($calendar);

                        if (!$focusable.length) return;

                        const $target = e.shiftKey
                            ? this.focusManager.resolveBackwardFocusTarget($calendar, $focused, $focusable.first(), $focusable.last())
                            : this.focusManager.resolveForwardFocusTarget($calendar, $focused, $focusable.first(), $focusable.last());

                        if (!$target) return;

                        e.preventDefault();
                        $target.focus();
                        return false;
                    }

                    case 'Enter':
                    case ' ': {
                        if ($focused.is('select') || $focused.is('option')) {
                            //time elements
                            return true;
                        }
                        e.preventDefault();

                        const isNavButton = $focused.hasClass('ui-datepicker-prev') || $focused.hasClass('ui-datepicker-next');

                        if (isNavButton) {
                            this.activateNavButton($focused, inputElement);
                            return false;
                        }

                        if (!this.focusManager.isDateCell($focused) || $focused.hasClass('ui-datepicker-unselectable')) {
                            return false;
                        }

                        if (e.key === 'Enter') {
                            $focused.find('a').click();
                            $.datepicker._hideDatepicker();
                            requestAnimationFrame(() => this.focusAfterClose());
                        } else {
                            $table.find('td.ui-datepicker-current-day')
                                .removeClass('ui-datepicker-current-day')
                                .removeAttr('aria-selected');
                            $focused.addClass('ui-datepicker-current-day').attr('aria-selected', 'true');
                        }

                        return false;
                    }

                    case 'Home':
                    case 'End':
                    case 'PageUp':
                    case 'PageDown':
                    case 'ArrowLeft':
                    case 'ArrowUp':
                    case 'ArrowRight':
                    case 'ArrowDown':
                        return this.handleGridNavigation(e, $calendar, inputElement);
                }
            });
        }
    }

    return KeyboardHandler;
});

