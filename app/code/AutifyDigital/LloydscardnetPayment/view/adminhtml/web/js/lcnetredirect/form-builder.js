/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
define(
    [
        'jquery',
        'underscore',
        'mage/template'
    ],
    function ($, _, mageTemplate) {
        'use strict';

        /* This is the form template used to generate the form, from your Controller JSON data */
        var form_template =
            '<form id="lloydscardsnetredirect-form" name="lloydscardsnetredirect-form" action="<%= data.action %>" method="POST" target="_blank" hidden enctype="application/x-www-form-urlencoded">' +
            '<% _.each(data.fields, function(val, key){ %>' +
            '<input value="<%= val %>" name="<%= key %>" type="hidden">' +
            '<% }); %>' +
            '</form>';

        return function (response) {
            var form = mageTemplate(form_template);
            var final_form = form({
                data: {
                    action: response.action,
                    fields: response.fields
                }
            });
            
            return $(final_form).appendTo($('[data-container="body"]'));
        };
    }
);