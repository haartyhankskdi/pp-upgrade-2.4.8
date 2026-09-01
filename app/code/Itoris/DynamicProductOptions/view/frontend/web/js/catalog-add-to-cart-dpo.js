/**
* Copyright © 2018 ITORIS INC. All rights reserved.
* See license agreement for details
*/

define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';
    
    return function (widget) {
        $.widget('mage.catalogAddToCart', widget, {
            submitForm: function (form) {
                var addToCartButton, self = this;

                if (form.find('input[type="file"]').length) {
                    //checking if at least one file has value (.val() won't work on multiple files)
                    var hasFile = false;
                    form.find('input[type="file"]').each(function(i, f){if (f.value) hasFile = true;});
                    if (hasFile) {                    
                        self.element.off('submit');
                        // disable 'Add to Cart' button
                        addToCartButton = $(form).find(this.options.addToCartButtonSelector);
                        addToCartButton.prop('disabled', true);
                        addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
                        form.submit();
                    } else {
                        self.ajaxSubmit(form);
                    }
                } else {
                    self.ajaxSubmit(form);
                }
            }
        });
        
        return $.mage.catalogAddToCart;
    }
});