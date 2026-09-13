define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.calendar', $.mage.calendar, {
            /**
             * Cancel overwriting.
             *
             * Fix issue https://github.com/magento/magento2/issues/39831
             */
            _overwriteFindPos: function () {}
        });

        return {
            dateRange: $.mage.dateRange,
            calendar: $.mage.calendar
        };
    }
});
