/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery",
    "jquery-ui-modules/widget",
    "domReady!"
], function ($) {
    'use strict';


    var notificationCount = $('.mst-notifications-wrapper').attr('data-mst-notification-count'),

        // Show notification details
        showNotificationDetails = function (notificationEntry) {
            var notificationDescription = notificationEntry.find('.notifications-entry-description'),
                notificationDescriptionEnd = notificationEntry.find('.notifications-entry-description-end');

            if (notificationDescriptionEnd.length > 0) {
                notificationDescriptionEnd.addClass('_show');
            }

            if(notificationDescription.hasClass('_cutted')) {
                notificationDescription.removeClass('_cutted');
            }
        };

    // Show notification description when corresponding item is clicked
    $('.mst-notifications-wrapper .admin__action-dropdown-menu .notifications-entry').on('click.showNotification', function (event) {
        // hide notification dropdown
        $('.mst-notifications-wrapper .notifications-icon').trigger('click.dropdown');

        showNotificationDetails($(this));
        event.stopPropagation();

    });

    // Remove corresponding notification from the list and mark it as read
    $('.notifications-close').on('click.removeNotification', function (event) {
        var notificationEntry = $(this).closest('.notifications-entry'),
            notificationId = notificationEntry.attr('data-notification-id');

        // Checking for last unread notification to hide dropdown
        if (notificationCount == 0) {
            $('.mst-notifications-wrapper').removeClass('active')
                .find('.notifications-action').removeAttr('data-toggle')
                .off('click.dropdown');
        }
        event.stopPropagation();
    });

    // Hide notifications bubble
    if (notificationCount == 0) {
        $('.notifications-action .mst-notifications-counter').hide();
    } else {
        $('.notifications-action .mst-notifications-counter').show();
    }

});
