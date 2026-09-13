define([
    'jquery',
    'Magento_Ui/js/form/provider'
], function ($, Provider) {
    return Provider.extend({
        save: function () {
            if (!this.validateDefaultField()) {
                return false;
            }
            document.dispatchEvent(new Event('customFormResetHiddenFields'));
            document.dispatchEvent(new Event('customFormSaveBefore'));

            return this._super();
        },

        validateDefaultField: function () {
            const fields = document.querySelectorAll('.fld-value.form-control');
            let isFilled = true;

            $('.admin__field-error').remove();
            $('.fld-value.form-control').removeClass('mage-error');

            for (let field of fields) {
                if (field.hasAttribute('required') && field.value === '') {

                    $(field).addClass('mage-error');

                    const error = $('<label>', {
                        class: 'admin__field-error',
                        text: 'This is a required field.'
                    });

                    const targetID = $(field).closest('.form-field').attr('id');
                    this.openField(targetID);

                    $(field).parent().find('.mage-error').after(error);
                    field.focus();
                    isFilled = false;

                    return;
                }
            }

            return isFilled;
        },

        openField: function (targetID) {
            const field = document.getElementById(targetID),
                toggleBtn = $('.toggle-form', field),
                editMode = $('.frm-holder', field);

            field.classList.add('editing');
            toggleBtn.add('open');
            if ( $('.prev-holder', field).is(':visible')) {
                $('.prev-holder', field).slideToggle(250);
            }
            editMode.slideDown(250);
        }
    });
});
