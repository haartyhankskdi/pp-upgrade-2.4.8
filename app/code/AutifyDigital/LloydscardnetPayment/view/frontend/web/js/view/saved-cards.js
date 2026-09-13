/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function ($, customerData, $t) {
    'use strict';

    return function (config, element) {
        var savedCardsSection = customerData.get('lloyds-saved-cards');

        function renderCards(cards) {
            var $content = $('#lloyds-saved-cards-content');
            var $loading = $('#lloyds-saved-cards-loading');
            
            $loading.hide();
            
            if (!cards || cards.length === 0) {
                $content.html('<p>' + $t('No saved cards found.') + '</p>');
                return;
            }

            var tableHtml = '<table class="data table table-saved-cards">' +
                '<thead><tr>' +
                '<th scope="col">' + $t('Masked') + '</th>' +
                '<th scope="col">' + $t('Brand') + '</th>' +
                '<th scope="col">' + $t('Last 4') + '</th>' +
                '<th scope="col">' + $t('Expiry Month') + '</th>' +
                '<th scope="col">' + $t('Expiry Year') + '</th>' +
                '<th scope="col">' + $t('Action') + '</th>' +
                '</tr></thead><tbody>';

            $.each(cards, function (index, card) {
                var masked = $('<div>').text(card.masked).html();
                var brand = $('<div>').text(card.brand).html();
                var last4 = $('<div>').text(card.last4).html();
                var expMonth = $('<div>').text(card.exp_month).html();
                var expYear = $('<div>').text(card.exp_year).html();
                var deleteUrl = $('<div>').text(card.delete_url).html();
                var cardId = $('<div>').text(card.id).html();
                
                tableHtml += '<tr data-card-id="' + cardId + '">' +
                    '<td>' + masked + '</td>' +
                    '<td>' + brand + '</td>' +
                    '<td>' + last4 + '</td>' +
                    '<td>' + expMonth + '</td>' +
                    '<td>' + expYear + '</td>' +
                    '<td><a href="#" class="delete-card" data-url="' + 
                        deleteUrl + '">' + 
                        $t('Delete') + '</a></td>' +
                    '</tr>';
            });

            tableHtml += '</tbody></table>';
            $content.html(tableHtml);
        }

        // Subscribe to section data updates
        savedCardsSection.subscribe(function (data) {
            if (data && data.cards !== undefined) {
                renderCards(data.cards);
            }
        });

        // Handle delete button clicks
        $(document).on('click', '.delete-card', function(e) {
            e.preventDefault();
            
            if (!confirm($t('Are you sure you want to delete this card?'))) {
                return;
            }
            
            var $link = $(this);
            var $row = $link.closest('tr');
            var deleteUrl = $link.data('url');
            
            $row.css('opacity', '0.5');
            $link.text($t('Deleting...'));
            
            $.ajax({
                url: deleteUrl,
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        customerData.invalidate(['lloyds-saved-cards']);
                        customerData.reload(['lloyds-saved-cards'], true);
                    });
                } else {
                    alert(response.message);
                    $row.css('opacity', '1');
                    $link.text($t('Delete'));
                }
            }).fail(function() {
                alert($t('Error deleting card. Please try again.'));
                $row.css('opacity', '1');
                $link.text($t('Delete'));
            });
        });

        $('#lloyds-saved-cards-loading').show();
        
        customerData.invalidate(['lloyds-saved-cards']);
        customerData.reload(['lloyds-saved-cards'], true);
        
        var initialData = savedCardsSection();
        if (initialData && initialData.cards !== undefined) {
            renderCards(initialData.cards);
        }
    };
});