define([
    'jquery',
], function ($) {
    $.widget('mage.dependency', {
        frequencySelector: '[data-index="frequency"]',
        cronSelector: '[data-index="cron_schedule"]',
        cronFieldsForChange: [
            '[data-index="hours"]',
            '[data-index="days"]',
            '[data-index="months"]',
            '[data-index="days_of_week"]'
        ],
        isNeedChange: true,

        _create: function () {
            var self = this;
            $(document).ajaxComplete(function() {
                self.addCronListener();
                self.addFrequencyListener();
            });
        },

        addCronListener: function () {
            var self = this;
            $(self.cronSelector).change(
                function () {
                    if (self.isNeedChange) {
                        $(self.frequencySelector + ' .admin__control-select').val(1).change();
                    }
                }
            );
        },

        addFrequencyListener: function () {
            var self = this;
            $(self.frequencySelector).change(
                function (event) {
                    switch (event.target.value) {
                        case '2':
                            self.setSchedule([9, '*', '*', '*']);
                            break;
                        case '3':
                            self.setSchedule([9, '*', '*', 1]);
                            break;
                        case '4':
                            self.setSchedule([9, 1, '*', '*']);
                            break;
                    }
                }
            );
        },

        setSchedule: function ($data) {
            this.isNeedChange = false;
            this.cronFieldsForChange.forEach(function ($value, index) {
                $($value).find('.admin__control-text').val($data[index]).change();
            });
            this.isNeedChange = true;
        }
    });

    return $.mage.dependency;
});
