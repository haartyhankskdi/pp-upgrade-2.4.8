/**
 * Copyright © 2019 Magenest. All rights reserved.
 */

define([
    'Magento_Ui/js/grid/columns/column',
    'jquery',
    'Magento_Ui/js/modal/modal',
    'ko'
], function (Element, $, modal, ko) {
    'use strict';

    return Element.extend({
        initObservable: function () {
            this._super();
            return this;
        },

        hasFieldAction: function () {
            return false;
        },

        displayModal: function (self, event) {
            var array = [];
            var html = '<table>';
            if(this.response_data) {
                array = JSON.parse(this.response_data);
            }
            $.each(array, function (k, v) {
                html+='<tr>';
                html+='<td>'+k+'</td>';
                html+='<td>&emsp;&emsp;'+v+'</td>';
                html+='</tr>';
            });
            html+='</table>';
            $('#modal-transaction').html(html);
            $('#modal-transaction').modal('openModal');
        }
    });
});
