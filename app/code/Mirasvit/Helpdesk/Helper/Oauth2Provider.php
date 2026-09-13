<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Helper;

use Mirasvit\Helpdesk\Model\Config\Source\AuthorizationType;

/**
 * Resolves the OAuth2 provider endpoints and scopes for a gateway.
 *
 * The gateway model, token storage, refresh loop and XOAUTH2 handshake are shared;
 * only the authorize/token/userinfo URLs and the scope string are provider-specific.
 * This helper turns a gateway authorization_type (and, for Microsoft, the tenant)
 * into that provider-specific set so the controllers and the token helper stay generic.
 */
class Oauth2Provider
{
    /**
     * Default Microsoft tenant when none is configured.
     * "common" lets both work/school and personal Microsoft accounts authenticate.
     */
    const DEFAULT_MICROSOFT_TENANT = 'common';

    /**
     * Whether the given authorization type is any OAuth2 provider.
     *
     * @param string $authorizationType
     * @return bool
     */
    public function isOauth2($authorizationType)
    {
        return in_array($authorizationType, [
            AuthorizationType::TYPE_OAUTH2,
            AuthorizationType::TYPE_OAUTH2_MICROSOFT,
        ], true);
    }

    /**
     * Whether the gateway uses the Microsoft 365 / Exchange Online provider.
     *
     * @param string $authorizationType
     * @return bool
     */
    public function isMicrosoft($authorizationType)
    {
        return $authorizationType === AuthorizationType::TYPE_OAUTH2_MICROSOFT;
    }

    /**
     * OAuth2 authorization endpoint (consent screen) for the provider.
     *
     * @param string      $authorizationType
     * @param string|null $tenant
     * @return string
     */
    public function getAuthorizeUrl($authorizationType, $tenant = null)
    {
        if ($this->isMicrosoft($authorizationType)) {
            return sprintf(
                'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize',
                $this->resolveTenant($tenant)
            );
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    /**
     * OAuth2 token endpoint (code exchange + refresh) for the provider.
     *
     * @param string      $authorizationType
     * @param string|null $tenant
     * @return string
     */
    public function getTokenUrl($authorizationType, $tenant = null)
    {
        if ($this->isMicrosoft($authorizationType)) {
            return sprintf(
                'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
                $this->resolveTenant($tenant)
            );
        }

        return 'https://oauth2.googleapis.com/token';
    }

    /**
     * Userinfo endpoint used to resolve the connected mailbox address.
     *
     * @param string $authorizationType
     * @return string
     */
    public function getUserInfoUrl($authorizationType)
    {
        if ($this->isMicrosoft($authorizationType)) {
            return 'https://graph.microsoft.com/v1.0/me';
        }

        return 'https://www.googleapis.com/oauth2/v1/userinfo';
    }

    /**
     * The key in the userinfo response that carries the mailbox address.
     * Microsoft Graph returns "mail" (falling back to "userPrincipalName");
     * Google userinfo returns "email".
     *
     * @param string $authorizationType
     * @return string[]
     */
    public function getUserInfoEmailKeys($authorizationType)
    {
        if ($this->isMicrosoft($authorizationType)) {
            return ['mail', 'userPrincipalName'];
        }

        return ['email'];
    }

    /**
     * Space-separated OAuth2 scope string for the provider.
     * Microsoft uses the Outlook IMAP delegated scope plus offline_access (refresh tokens).
     *
     * @param string $authorizationType
     * @return string
     */
    public function getScope($authorizationType)
    {
        // OAuth2 scope tokens are machine protocol values sent to the provider, never shown to a
        // user - they must not be translated. They are assembled from discrete tokens (rather than
        // one space-separated literal) so each stays an opaque identifier.
        if ($this->isMicrosoft($authorizationType)) {
            // Mail is fetched through Microsoft Graph (PHP's imap extension cannot do XOAUTH2 on
            // this stack), so the resource scope is Graph Mail.ReadWrite - read messages, mark them
            // read and delete them. openid/profile/email add the id_token that carries the mailbox
            // address; offline_access yields the refresh token. OIDC scopes may be combined with a
            // single resource scope on the v2.0 endpoint.
            return implode(' ', [
                'openid',
                'profile',
                'email',
                'offline_access',
                'https://graph.microsoft.com/Mail.ReadWrite',
            ]);
        }

        return implode(' ', [
            'https://www.googleapis.com/auth/gmail.modify',
            'https://www.googleapis.com/auth/userinfo.email',
        ]);
    }

    /**
     * Default IMAP host for the provider (used as a hint; the gateway host field still wins).
     *
     * @param string $authorizationType
     * @return string
     */
    public function getImapHost($authorizationType)
    {
        if ($this->isMicrosoft($authorizationType)) {
            return 'outlook.office365.com';
        }

        return 'imap.gmail.com';
    }

    /**
     * Admin route of the OAuth2 callback controller for the provider.
     *
     * Each provider has its own callback action so the redirect URI shown in the gateway
     * form (and registered in the provider console) is self-describing: Microsoft 365 uses
     * .../gateway/microsoftAuth, while Gmail keeps the original .../gateway/gmailAuth so
     * existing Google Cloud Console registrations continue to work.
     *
     * The same route is used both when starting the flow (authorize request) and when
     * exchanging the code for tokens, so the redirect URI always matches what was registered.
     *
     * @param string $authorizationType
     * @return string
     */
    public function getCallbackRoute($authorizationType)
    {
        if ($this->isMicrosoft($authorizationType)) {
            return 'helpdesk/gateway/microsoftAuth';
        }

        return 'helpdesk/gateway/gmailAuth';
    }

    /**
     * Extract the mailbox address from an OIDC id_token (Microsoft 365).
     *
     * The Outlook-scoped access token cannot call Microsoft Graph, so the mailbox address is
     * read from the id_token returned in the same token response. The token is delivered
     * directly from the provider token endpoint over TLS and used only to display/store the
     * address, so the payload is decoded without signature verification.
     *
     * @param string|null $idToken
     * @return string|null
     */
    public function extractEmailFromIdToken($idToken)
    {
        if (!is_string($idToken) || strpos($idToken, '.') === false) {
            return null;
        }

        $parts = explode('.', $idToken);
        if (count($parts) < 2) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (!is_array($payload)) {
            return null;
        }

        // Work/school accounts expose the address as preferred_username/upn; email is present
        // when a mailbox claim is available. Accept the first value that looks like an address.
        foreach (['email', 'preferred_username', 'upn'] as $claim) {
            if (!empty($payload[$claim]) && strpos($payload[$claim], '@') !== false) {
                return $payload[$claim];
            }
        }

        return null;
    }

    /**
     * Decode a base64url segment (JWT parts) into its raw string.
     *
     * @param string $data
     * @return string
     */
    private function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string)base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Normalize an optional tenant id to a usable Azure AD tenant segment.
     *
     * @param string|null $tenant
     * @return string
     */
    public function resolveTenant($tenant)
    {
        $tenant = is_string($tenant) ? trim($tenant) : '';

        return $tenant !== '' ? $tenant : self::DEFAULT_MICROSOFT_TENANT;
    }
}
