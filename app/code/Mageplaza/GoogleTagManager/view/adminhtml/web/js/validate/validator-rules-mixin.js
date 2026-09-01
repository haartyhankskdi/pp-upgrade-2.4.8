define([
    'jquery',
], function ($, $t) {
    'use strict';

    return function (target) {
        $.validator.addMethod(
            'validate-measurement-id',
            function (value) {
                var regex = '^G-';
                if (value.length) {
                    return value.trim().match(regex);
                } else {
                    return true;
                }
            },
            $.mage.__('Please enter a valid measurement ID')
        );

        return target;
    }
})
