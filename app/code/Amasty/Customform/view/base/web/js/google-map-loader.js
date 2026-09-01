define([
    'jquery'
], function ($) {
    'use_strict';

    /**
     * @param {string} googleKey
     *
     * @return {jQuery.Deferred}
     */
    return function (googleKey) {
        var result = $.Deferred();

        try {
            require(
                ['https://maps.googleapis.com/maps/api/js?libraries=places&key=' + googleKey],
                result.resolve.bind(result)
            );
        } catch (e) {
            result.reject();
        }

        return result;
    }
})
