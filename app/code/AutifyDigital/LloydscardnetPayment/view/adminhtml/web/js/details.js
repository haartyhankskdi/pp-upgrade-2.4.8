/**
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */

define([
    'Magento_Ui/js/grid/columns/column',
    'jquery',
    'Magento_Ui/js/modal/modal'
], function (Column, $, modal) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'ui/grid/cells/html',
            fieldClass: {
                'data-grid-html-cell': true
            }
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.initClickHandler();
            return this;
        },

        /**
         * Initialize click handler for payment details
         */
        initClickHandler: function () {
            var self = this;
            
            $(document).on('click', '.action-view-payment-details', function (e) {
                e.preventDefault();
                
                var $row = $(this).closest('tr');
                var detailsHtml = $row.find('[data-column="details"]').attr('data-details-html');
                
                if (!detailsHtml) {
                    var $detailsCell = $row.find('td[data-column="details"]');
                    detailsHtml = $detailsCell.data('details-html') || $detailsCell.find('.details-html').html();
                }

                if (detailsHtml) {
                    self.showPaymentDetailsModal(detailsHtml);
                }
            });
        },

        /**
         * Show payment details modal
         */
        showPaymentDetailsModal: function (htmlContent) {
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: 'Payment Details',
                modalClass: 'lloyd-cardnet-payment-details-modal'
            };

            var popup = modal(options, $('<div></div>').html(htmlContent));
            popup.openModal();
        },

        /**
         * Get field handler
         */
        getFieldHandler: function (record) {
            var self = this;
            
            return function () {
                var detailsHtml = record[self.index + '_html'];
                if (detailsHtml) {
                    self.showPaymentDetailsModal(detailsHtml);
                }
            };
        }
    });
});
