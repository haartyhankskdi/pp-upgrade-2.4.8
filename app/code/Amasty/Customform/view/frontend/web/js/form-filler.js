define([
    'jquery',
    'jquery/validate',
    'Magento_Ui/js/modal/modal'
], function ($) {
    'use_strict';

    $.widget('mage.amFormFill', {
        options: {
           formParams: {
               urlSession: '',
               formId: 0,
               productId: 0
           },
            selectors: {
                input: '.form-control, .amform-date, .amform-time',
                field: '.field'
            }
        },
        classes: {
            hasContent: '-has-content',
            active: '-active',
            error: '-error'
        },

        _create: function () {
            var source = this.getDataSource();

            source.fail(this.processError.bind(this));
            source.then(this.checkFieldType.bind(this));
        },

        /**
         * @param {Error} error
         */
        processError: function (error) {
            console.log(error);
        },

        /**
         * @return {jQuery.Deferred}
         */
        getDataSource: function () {
            var result = $.Deferred();

            $.ajax({
                url: this.options.formParams.urlSession,
                dataType: 'json',
                type: 'get',
                data: {'form_id': this.options.formParams.formId, 'product_id': this.options.formParams.productId},
                success: function(response) {
                    result.resolve(response.form_fields || {});
                },
                error: function(error) {
                    result.reject(error);
                }
            });

            return result;
        },

        /**
         * @param {Object} fields
         */
        checkFieldType: function (fields) {
            var formData = this.element,
                field = '';

            $.each(fields, function (name, value) {
                field = formData.find('[data-amcform-js="' + name + '"]');

                if (field.length) {
                    switch (field.attr('type')) {
                        case 'select':
                            var optionItems = field.children();
                            $.each(optionItems, function (item, option) {
                                $(option).prop('selected', false);
                                var currentOptValue = $(option).val();
                                if (currentOptValue === value
                                    || (Array.isArray(value)
                                        && value.includes(currentOptValue))
                                ) {
                                    $(this).prop('selected', true);
                                }
                            });
                            break;
                        case 'radio':
                            var checkedField = value.split('-'),
                                selector = name + '-' + (checkedField[1] - 1);

                            $('#' + selector).prop('checked', true);
                            break;
                        case 'checkbox':
                            field.prop('checked', false);
                            $.each(value, function (index, val) {
                                var selector = '[data-amcform-js="' + name + '"]' + '[value="' + val + '"]';

                                $(selector).prop('checked', true);
                            });
                            break;
                        case 'googlemap':
                            var googleMap = field.closest('.fb-googlemap');

                            if (value instanceof Object
                                && typeof value.lng !== 'undefined'
                                && googleMap.data('mageAmGoogleMap')
                            ) {
                                googleMap.amGoogleMap('moveMarker', value);
                            }

                            break;
                        default:
                            field.val(value);
                    }
                }
            });

            this.animateField();
        },

        animateField: function () {
            var self = this,
                input = $(self.options.selectors.input),
                activeClass = self.classes.active,
                hasContentClass = self.classes.hasContent;

            input.each(function () {
                if (this.value) {
                    $(this).closest(self.options.selectors.field).addClass(hasContentClass);
                }
            });

            input.on('focusin', function() {
                var parent = $(this).closest(self.options.selectors.field);
                parent.addClass(activeClass);
            });

            input.on('focusout', function() {
                var parent = $(this).closest(self.options.selectors.field);
                parent.removeClass(activeClass);
            });

            input.on('change', function() {
                var parent = $(this).closest(self.options.selectors.field);

                if (this.value) {
                    parent.addClass(hasContentClass);
                } else {
                    parent.removeClass(hasContentClass);
                }
            })
        }
    });

    return $.mage.amFormFill;
});
