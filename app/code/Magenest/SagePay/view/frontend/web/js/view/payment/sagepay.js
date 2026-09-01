/**
 * Copyright © 2019 Magenest. All rights reserved.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'magenest_sagepay',
                component: 'Magenest_SagePay/js/view/payment/method-renderer/sagepay-renderer'
            },
            {
                type: 'magenest_sagepay_form',
                component: 'Magenest_SagePay/js/view/payment/method-renderer/sagepay-form'
            },
            {
                type: 'magenest_sagepay_direct',
                component: 'Magenest_SagePay/js/view/payment/method-renderer/sagepay-direct'
            },
            {
                type: 'magenest_sagepay_server',
                component: 'Magenest_SagePay/js/view/payment/method-renderer/sagepay-server'
            },
            {
                type: 'magenest_sagepay_paypal',
                component: 'Magenest_SagePay/js/view/payment/method-renderer/sagepay-paypal'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);