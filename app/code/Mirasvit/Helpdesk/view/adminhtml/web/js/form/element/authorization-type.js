define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function (Select, registry) {
    'use strict';

    return Select.extend({
        defaults: {
            listens: {
                value: 'onValueChange'
            }
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.onValueChange(this.value());
            return this;
        },

        /**
         * Handle value change
         */
        onValueChange: function (value) {
            var imapFields = [
                'email',
                'login',
                'password',
                'host',
                'folder',
                'protocol',
                'encryption',
                'port'
            ];

            var oauth2Fields = [
                'client_id',
                'client_secret',
                'redirect_uri',
                'connection_status',
                'oauth_connect_button'
            ];

            // Microsoft 365 also needs the Azure AD tenant id.
            var microsoftFields = ['tenant'];

            // Per-provider, where-to-get-the-values instruction blocks (with links).
            var gmailInstructions = ['oauth_gmail_instructions'];
            var microsoftInstructions = ['oauth_microsoft_instructions'];

            var formName = 'gateway_edit_form.gateway_edit_form.general.';

            if (value === 'imap') {
                this.toggleFields(imapFields, formName, true);
                this.toggleFields(oauth2Fields, formName, false);
                this.toggleFields(microsoftFields, formName, false);
                this.toggleFields(gmailInstructions, formName, false);
                this.toggleFields(microsoftInstructions, formName, false);
            } else if (value === 'oauth2') {
                this.toggleFields(imapFields, formName, false);
                this.toggleFields(oauth2Fields, formName, true);
                this.toggleFields(microsoftFields, formName, false);
                this.toggleFields(gmailInstructions, formName, true);
                this.toggleFields(microsoftInstructions, formName, false);
                this.updateRedirectUri();
            } else if (value === 'oauth2_microsoft') {
                this.toggleFields(imapFields, formName, false);
                this.toggleFields(oauth2Fields, formName, true);
                this.toggleFields(microsoftFields, formName, true);
                this.toggleFields(gmailInstructions, formName, false);
                this.toggleFields(microsoftInstructions, formName, true);
                this.updateRedirectUri();
            }
        },

        /**
         * Toggle field visibility
         */
        toggleFields: function (fields, formName, visible) {
            fields.forEach(function (fieldName) {
                var field = registry.get(formName + fieldName);
                if (field) {
                    field.visible(visible);
                }
            });
        },

        /**
         * Update redirect URI field with the provider-specific callback URL.
         *
         * window.BASE_URL in the admin is set to {admin_base}/{front_name}/index/index/key/{key}/
         * (see vendor/magento/module-backend/view/adminhtml/templates/page/js/require_js.phtml).
         * Strip the front-name + index/index/key/{key}/ suffix to get the admin root URL,
         * then append the callback path for the selected provider: microsoftAuth for
         * Microsoft 365, gmailAuth for Gmail. This mirrors Oauth2Provider::getCallbackRoute()
         * so the value shown here matches the redirect URI the backend actually uses.
         * The URL intentionally omits the secret key so it is stable across sessions and
         * can be pre-registered in the provider console (Google Cloud Console / Azure).
         */
        updateRedirectUri: function () {
            var redirectUriField = registry.get('gateway_edit_form.gateway_edit_form.general.redirect_uri');
            if (redirectUriField) {
                var adminBase = (window.BASE_URL || '').replace(/[^\/]+\/index\/index\/key\/[^\/]+\/$/, '');
                var action = this.value() === 'oauth2_microsoft' ? 'microsoftAuth' : 'gmailAuth';
                redirectUriField.value(adminBase + 'helpdesk/gateway/' + action + '/');
            }
        }
    });
});
