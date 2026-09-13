/**
 * ARIA Attributes Manager
 *
 * Manages ARIA attributes for the datepicker calendar
 */
define([
    'jquery',
    'mage/translate',
    'mage/utils/misc'
], function ($, $t, utils) {
    'use strict';

    /**
     * ARIA Manager Class
     */
    class AriaManager {
        /**
         * Sets ARIA attributes for the calendar dialog
         * @param {jQuery} $calendar - The calendar jQuery element
         */
        isDateTimePicker($calendar) {
            return $calendar && $calendar.find('.ui-timepicker-div').length > 0;
        }

        setAriaAttributes($calendar) {
            const label = this.isDateTimePicker($calendar) ? $t('Choose Date and Time') : $t('Choose Date');

            $calendar.attr({
                'role': 'dialog',
                'aria-modal': 'true',
                'aria-label': label,
                'tabindex': '-1'
            });
        }

        /**
         * Sets up the calendar table grid with proper ARIA attributes
         * @param {jQuery} $calendar - The calendar jQuery element
         * @returns {{$table: jQuery, $title: jQuery}} Object containing table and title elements
         */
        setupTableGrid($calendar) {
            const $table = $calendar.find('.ui-datepicker-calendar');

            $table.attr({
                'role': 'grid',
                'aria-labelledby': 'ui-datepicker-title'
            });

            const $header = $calendar.find('.ui-datepicker-header');
            const $title = $header.find('.ui-datepicker-title');

            if (!$title.attr('id')) {
                $title.attr('id', 'ui-datepicker-title');
            }

            $title.attr('aria-live', 'polite');

            return {$table, $title};
        }

        /**
         * Sets up table structure with proper ARIA roles for rows and headers
         * @param {jQuery} $table - The table jQuery element
         */
        setupTableStructure($table) {
            $table.find('thead tr').attr('role', 'row');
            $table.find('tbody tr').attr('role', 'row');

            $table.find('thead th').each(function() {
                $(this).attr({
                    'role': 'columnheader',
                    'aria-label': $(this).text()
                });
            });
        }

        /**
         * Builds a human-readable aria-label for a date cell link
         * @param {string} day - Day number as text
         * @param {string} month - Month/year string from the calendar title
         * @param {boolean} isToday
         * @param {boolean} isSelected
         * @returns {string}
         */
        buildDateAriaLabel(day, month, isToday, isSelected) {
            let label = day + ' ' + month;

            if (isToday) label += ', ' + $t('today');
            if (isSelected) label += ', ' + $t('selected');

            return label;
        }

        /**
         * Formats a data-date attribute value (YYYY-MM-DD) from link data attributes.
         * Returns null when the required attributes are missing or invalid.
         * @param {jQuery} $link
         * @returns {string|null}
         */
        formatDataDate($link) {
            const dayNum = parseInt($link.text());

            if (isNaN(dayNum) || !$link.attr('data-month') || !$link.attr('data-year')) {
                return null;
            }

            const monthNum = parseInt($link.attr('data-month'));
            const yearNum = parseInt($link.attr('data-year'));
            const d = String(dayNum).padStart(2, '0');
            const m = String(monthNum + 1).padStart(2, '0');

            return yearNum + '-' + m + '-' + d;
        }

        /**
         * Sets up date cells with ARIA attributes and labels
         * @param {jQuery} $table - The table jQuery element
         * @param {jQuery} $title - The title jQuery element containing month/year
         */
        setupDateCells($table, $title) {
            const month = $title.text();

            $table.find('tbody td').each((_, el) => {
                const $cell = $(el);
                const $link = $cell.find('a');
                const $span = $cell.find('span');

                $cell.attr('role', 'gridcell');

                if ($link.length > 0) {
                    const isToday = $cell.hasClass('ui-datepicker-today');
                    const isSelected = $cell.hasClass('ui-datepicker-current-day');
                    const dataDate = this.formatDataDate($link);

                    if (dataDate) {
                        $cell.attr('data-date', dataDate);
                    }

                    $link.attr({
                        'role': 'button',
                        'aria-label': this.buildDateAriaLabel($link.text(), month, isToday, isSelected),
                        'aria-current': isToday ? 'date' : null,
                        'tabindex': '-1'
                    });

                    if (isSelected) {
                        $cell.attr('aria-selected', 'true');
                    } else {
                        $cell.removeAttr('aria-selected');
                    }
                } else if ($span.length > 0) {
                    $cell.attr('aria-disabled', 'true');
                    $span.attr('aria-hidden', 'true');
                }
            });
        }

        /**
         * Builds a verbose "Change Date, Monday 1 January, 2026" label
         * from a datepicker instance. Returns null when data is unavailable.
         * @param {Object} inst - jQuery UI datepicker instance
         * @returns {string|null}
         */
        formatSelectedDateLabel(inst, isDateTime) {
            if (!inst || !inst.selectedYear || inst.selectedMonth === undefined || !inst.selectedDay) {
                return null;
            }

            const dayLabels = [
                $t('Sunday'), $t('Monday'), $t('Tuesday'), $t('Wednesday'),
                $t('Thursday'), $t('Friday'), $t('Saturday')
            ];
            const monthLabels = [
                $t('January'), $t('February'), $t('March'), $t('April'),
                $t('May'), $t('June'), $t('July'), $t('August'),
                $t('September'), $t('October'), $t('November'), $t('December')
            ];

            const date = new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay);
            const prefix = isDateTime ? $t('Change Date and Time') : $t('Change Date');

            return prefix
                + ', ' + dayLabels[date.getDay()]
                + ' ' + date.getDate()
                + ' ' + monthLabels[date.getMonth()]
                + ', ' + date.getFullYear();
        }

        /**
         * @param {jQuery} $calendar - The calendar jQuery element
         */
        setupTimeLabels($calendar) {
            const $timepickerDiv = $calendar.find('.ui-timepicker-div');

            if (!$timepickerDiv.length) {
                return;
            }

            const $timeInputs = $timepickerDiv.find('dd:not(.ui_tpicker_unit_hide) [data-unit]');

            $timeInputs.each((key, el) => {
                const $el = $(el);
                const unit = el.dataset.unit;
                const id = utils.uniqueid();
                const labelContainer = $timepickerDiv.find('.ui_tpicker_' + unit + '_label');
                const label = document.createElement('label');

                el.id = id;
                label.textContent = labelContainer.text();
                label.setAttribute('for', id);
                label.classList.add('label');
                labelContainer.html(label);

                $el.off('change.am-timepicker-focus').on('change.am-timepicker-focus', (e) => {
                    this.retainTimeSelectFocus($calendar, e.target.dataset.unit);
                });
            })
        }

        /**
         * jQuery UI Timepicker Addon (controlType: 'select') discards and recreates
         * every unit <select> on each change to apply updated min/max/step constraints,
         * which drops focus from the option the user just picked. Restores ARIA labels
         * and refocuses the freshly created control for the same time unit once the
         * rebuild completes.
         * @param {jQuery} $calendar - The calendar jQuery element
         * @param {string} unit - The time unit of the changed control (e.g. 'hour', 'minute')
         */
        retainTimeSelectFocus($calendar, unit) {
            if (!unit) {
                return;
            }

            requestAnimationFrame(() => {
                this.setupTimeLabels($calendar);

                const [select] = $calendar.find(`.ui-timepicker-select[data-unit="${unit}"]`);

                select?.focus();
            });
        }

        /**
         * Updates the ARIA label of the input field with the selected date
         * @param {jQuery} $input - The input jQuery element
         */
        updateInputLabel($input) {
            let label;
            const inst = $.datepicker._getInst($input[0]);
            const isDateTime = inst ? this.isDateTimePicker(inst.dpDiv) : false;
            const choosePlaceholder = isDateTime ? $t('Choose Date and Time') : $t('Choose Date');

            if (!$input.val()) {
                label = choosePlaceholder;
            } else {
                try {
                    label = this.formatSelectedDateLabel(inst, isDateTime) || choosePlaceholder;
                } catch (e) {
                    label = choosePlaceholder;
                }
            }

            $input.attr('aria-label', label);

            const $trigger = $input.next('.ui-datepicker-trigger');

            if ($trigger.length) {
                $trigger.attr('aria-label', label);
            }
        }
    }

    return AriaManager;
});

