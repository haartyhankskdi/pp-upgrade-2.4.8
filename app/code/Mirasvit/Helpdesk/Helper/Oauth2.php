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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;
use Mirasvit\Helpdesk\Model\Gateway;

class Oauth2 extends AbstractHelper
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Oauth2Provider
     */
    protected $provider;

    /**
     * @param Context $context
     * @param Curl $curl
     * @param Oauth2Provider $provider
     */
    public function __construct(
        Context $context,
        Curl $curl,
        Oauth2Provider $provider
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->provider = $provider;
    }

    /**
     * Check if token is expired
     *
     * @param Gateway $gateway
     * @return bool
     */
    public function isTokenExpired($gateway)
    {
        $tokenExpires = $gateway->getTokenExpires();

        if (!$tokenExpires) {
            return true;
        }

        // Add 5 minute buffer before actual expiration
        return (time() + 300) >= $tokenExpires;
    }

    /**
     * Refresh OAuth2 access token
     *
     * @param Gateway $gateway
     * @return bool
     * @throws \Exception
     */
    public function refreshAccessToken($gateway)
    {
        $refreshToken = $gateway->getRefreshToken();

        if (!$refreshToken) {
            throw new \Exception('No refresh token available. Please reconnect the mailbox.');
        }

        $tokenUrl = $this->provider->getTokenUrl(
            $gateway->getAuthorizationType(),
            $gateway->getTenant()
        );

        $postData = [
            'client_id' => $gateway->getClientId(),
            'client_secret' => $gateway->getClientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ];

        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_POST, true);
        $this->curl->setOption(CURLOPT_POSTFIELDS, http_build_query($postData));
        $this->curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $this->curl->post($tokenUrl, $postData);
        $response = $this->curl->getBody();

        $tokens = json_decode($response, true);

        if (isset($tokens['error'])) {
            throw new \Exception('Token refresh failed: ' . ($tokens['error_description'] ?? $tokens['error']));
        }

        if (isset($tokens['access_token'])) {
            // Update access token
            $gateway->setAccessToken($tokens['access_token']);

            // Update refresh token if a new one is provided
            if (isset($tokens['refresh_token'])) {
                $gateway->setRefreshToken($tokens['refresh_token']);
            }

            // Update expiration time
            $expiresIn = isset($tokens['expires_in']) ? (int)$tokens['expires_in'] : 3600;
            $gateway->setTokenExpires(time() + $expiresIn);

            $gateway->save();

            return true;
        }

        throw new \Exception('Failed to refresh access token.');
    }

    /**
     * Ensure gateway has a valid access token
     *
     * @param Gateway $gateway
     * @return string
     * @throws \Exception
     */
    public function ensureValidAccessToken($gateway)
    {
        if ($this->isTokenExpired($gateway)) {
            $this->refreshAccessToken($gateway);
        }

        return $gateway->getAccessToken();
    }

    /**
     * Generate XOAUTH2 authentication string for IMAP
     *
     * @param string $email
     * @param string $accessToken
     * @return string
     */
    public function generateXOAuth2String($email, $accessToken)
    {
        $authString = sprintf(
            "user=%s\1auth=Bearer %s\1\1",
            $email,
            $accessToken
        );

        return base64_encode($authString);
    }
}
