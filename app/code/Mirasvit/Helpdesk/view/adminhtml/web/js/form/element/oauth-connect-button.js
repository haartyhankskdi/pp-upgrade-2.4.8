define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'jquery'
], function (Abstract, registry, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            template: 'Mirasvit_Helpdesk/form/element/oauth-connect-button'
        },

        /**
         * Return true when a Gmail account is already connected
         */
        isConnected: function () {
            var statusField = registry.get('gateway_edit_form.gateway_edit_form.general.connection_status');
            var status = statusField ? statusField.value() : '';
            return typeof status === 'string' && status.indexOf('Connected') === 0;
        },

        /**
         * Get the gateway ID from the form
         */
        getGatewayId: function () {
            var gatewayIdField = registry.get('gateway_edit_form.gateway_edit_form.general.gateway_id');
            return gatewayIdField ? gatewayIdField.value() : null;
        },

        /**
         * Handle connect button click
         */
        connectToGmail: function () {
            var gatewayId = this.getGatewayId();

            if (!gatewayId) {
                alert($.mage.__('Please save the gateway first before connecting the mailbox.'));
                return;
            }

            var clientId = registry.get('gateway_edit_form.gateway_edit_form.general.client_id');
            var clientSecret = registry.get('gateway_edit_form.gateway_edit_form.general.client_secret');

            if (!clientId || !clientId.value() || !clientSecret || !clientSecret.value()) {
                alert($.mage.__('Please enter Client ID and Client Secret before connecting.'));
                return;
            }

            // The connect URL is built server-side (Mirasvit\Helpdesk\Ui\Form\Gateway\DataProvider::getMeta)
            // so it carries the admin secret key for the gateway_oauth/connect action, which is
            // required when "Add Secret Key to URLs" is enabled and can only be generated server-side.
            if (!this.connectUrl) {
                alert($.mage.__('Cannot determine admin URL. Please refresh the page.'));
                return;
            }

            window.open(
                this.connectUrl,
                'gmail_oauth_connect',
                'width=600,height=700,resizable=yes,scrollbars=yes'
            );
        }

    });
});
