/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
define(['mage/translate'], function ($t) {
    'use strict';

    /**
     * PaymentJS reports every client side validation failure with this single message and
     * puts the detail in error.data, an array of the field validity objects that failed.
     * A card whose brand is not in fields.card.allowedBrands fails the same way, so the
     * brand has to be read out of error.data to tell the shopper what actually went wrong.
     *
     * @see https://docs.paymentjs.firstdata.com/
     */
    var VALIDATION_FAILED = 'form validation failed';

    var FIELD_LABELS = {
        card: $t('card number'),
        cvv: $t('security code'),
        exp: $t('expiry date'),
        name: $t('name on the card')
    };

    var BRAND_LABELS = {
        'american-express': $t('American Express'),
        'diners-club': $t('Diners Club'),
        'discover': $t('Discover'),
        'electron': $t('Electron'),
        'elo': $t('Elo'),
        'jcb': $t('JCB'),
        'maestro': $t('Maestro'),
        'mastercard': $t('Master Card'),
        'mir': $t('Mir'),
        'unionpay': $t('Unionpay'),
        'visa': $t('Visa')
    };

    function brandLabel(brand) {
        return BRAND_LABELS[brand] || brand;
    }

    function invalidFields(error) {
        return error && Array.isArray(error.data) ? error.data : [];
    }

    function findCardField(fields) {
        var i;

        for (i = 0; i < fields.length; i++) {
            if (fields[i].field === 'card') {
                return fields[i];
            }
        }

        return null;
    }

    /**
     * Turn a PaymentJS tokenize error into a message that tells the shopper what to fix.
     *
     * @param {Object} error - the error handed to the onSubmit failure callback
     * @param {Array} allowedBrands - the brands configured on fields.card.allowedBrands
     * @return {String}
     */
    return function getPaymentJsErrorMessage(error, allowedBrands) {
        var fields, card, brands, labels;

        if (!error || error.message !== VALIDATION_FAILED) {
            return error && error.message
                ? error.message
                : $t('Something went wrong with the payment. Please retry!');
        }

        fields = invalidFields(error);
        card = findCardField(fields);
        brands = allowedBrands || [];

        if (card && card.brand && brands.length > 0 && brands.indexOf(card.brand) === -1) {
            return $t('We do not accept %1 cards. Please use one of the following: %2.')
                .replace('%1', card.brandNiceType || brandLabel(card.brand))
                .replace('%2', brands.map(brandLabel).join(', '));
        }

        labels = fields.map(function (field) {
            return FIELD_LABELS[field.field] || field.field;
        });

        if (!labels.length) {
            return $t('Please check your card details and try again.');
        }

        return $t('Please check your %1 and try again.').replace('%1', labels.join(', '));
    };
});
