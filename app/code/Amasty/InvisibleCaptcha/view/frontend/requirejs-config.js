var config = {
    config: {
        mixins: {
            'Magento_Paypal/js/view/payment/method-renderer/in-context/checkout-express': {
                'Amasty_InvisibleCaptcha/js/view/paypal/in-context/checkout-express-mixin': true
            },
            'Magento_Customer/js/model/authentication-popup': {
                'Amasty_InvisibleCaptcha/js/mixins/authentication_popup': true
            },
            'Magento_Checkout/js/view/form/element/email': {
                'Amasty_InvisibleCaptcha/js/mixins/email-recaptcha-mixin': true
            },
            'Magento_Checkout/js/view/authentication': {
                'Amasty_InvisibleCaptcha/js/mixins/checkout-authentication-mixin': true
            },
        }
    }
};
