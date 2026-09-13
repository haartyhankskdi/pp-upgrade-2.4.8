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



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Gateway;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\HTTP\Client\Curl;
use Mirasvit\Helpdesk\Model\GatewayFactory;

class GmailAuth extends Action implements CsrfAwareActionInterface
{
    const ADMIN_RESOURCE = 'Mirasvit_Helpdesk::helpdesk_gateway';

    /**
     * Google redirects back to this callback URL without Magento's session secret key.
     * Bypass the secret key check; the OAuth state parameter (CSRF token stored in session)
     * provides equivalent protection for this specific callback endpoint.
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Skip the admin secret-key check for the OAuth2 callback.
     *
     * Google redirects the browser back here as a GET without Magento's session-bound admin
     * secret key, so when "Add Secret Key to URLs" (admin/security/use_form_key) is enabled
     * \Magento\Backend\App\AbstractAction::_processUrlKeys() fails the secret-key check and
     * redirects to the admin dashboard (getStartupPageUrl()) before execute() ever runs.
     *
     * CsrfAwareActionInterface does NOT cover this check — it only affects the framework
     * form-key/CSRF validator. Forgery protection for this endpoint is instead provided by
     * the signed `state` parameter validated in execute().
     *
     * @return bool
     */
    protected function _validateSecretKey()
    {
        return true;
    }

    /**
     * @var GatewayFactory
     */
    protected $gatewayFactory;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Oauth2Provider
     */
    protected $provider;

    /**
     * @param Context $context
     * @param GatewayFactory $gatewayFactory
     * @param Curl $curl
     * @param \Mirasvit\Helpdesk\Helper\Oauth2Provider $provider
     */
    public function __construct(
        Context $context,
        GatewayFactory $gatewayFactory,
        Curl $curl,
        \Mirasvit\Helpdesk\Helper\Oauth2Provider $provider
    ) {
        parent::__construct($context);
        $this->gatewayFactory = $gatewayFactory;
        $this->curl = $curl;
        $this->provider = $provider;
    }

    /**
     * Execute OAuth2 callback action
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $code  = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');
        $error = $this->getRequest()->getParam('error');

        // Handle OAuth error
        if ($error) {
            $this->messageManager->addErrorMessage(__('OAuth2 authorization failed: %1', $error));
            return $this->redirectOpener('helpdesk/gateway/index');
        }

        if (!$code || !$state) {
            $this->messageManager->addErrorMessage(__('Invalid OAuth2 callback parameters.'));
            return $this->redirectOpener('helpdesk/gateway/index');
        }

        // Decode state parameter
        $stateDecoded = base64_decode($state);
        $stateParts   = explode('|', $stateDecoded);

        if (count($stateParts) !== 2) {
            $this->messageManager->addErrorMessage(__('Invalid state parameter.'));
            return $this->redirectOpener('helpdesk/gateway/index');
        }

        list($gatewayId, $csrfToken) = $stateParts;

        // Verify CSRF token
        $storedToken = $this->_session->getData('oauth2_csrf_token_' . $gatewayId);
        if ($storedToken !== $csrfToken) {
            $this->messageManager->addErrorMessage(__('CSRF token validation failed.'));
            return $this->redirectOpener('helpdesk/gateway/index');
        }

        // Clear the CSRF token
        $this->_session->unsetData('oauth2_csrf_token_' . $gatewayId);

        // Load gateway
        $gateway = $this->gatewayFactory->create()->load((int) $gatewayId);

        if (!$gateway->getId()) {
            $this->messageManager->addErrorMessage(__('Gateway not found.'));
            return $this->redirectOpener('helpdesk/gateway/index');
        }

        // Exchange authorization code for tokens
        try {
            $tokens = $this->exchangeCodeForTokens($code, $gateway);

            if (isset($tokens['access_token'])) {
                // Save tokens to gateway
                $gateway->setAccessToken($tokens['access_token']);

                if (isset($tokens['refresh_token'])) {
                    $gateway->setRefreshToken($tokens['refresh_token']);
                }

                $expiresIn = isset($tokens['expires_in']) ? (int)$tokens['expires_in'] : 3600;
                $gateway->setTokenExpires(time() + $expiresIn);
                $gateway->setConnectionStatus(__('Connected'));

                // Resolve the connected mailbox address (id_token for Microsoft, userinfo for Google)
                $email = $this->fetchAccountEmail($tokens, $gateway->getAuthorizationType());
                if ($email) {
                    $gateway->setConnectionStatus(__('Connected to %1', $email));
                    $gateway->setEmail($email);
                } else {
                    $gateway->setConnectionStatus(__('No email connected'));
                }

                $gateway->save();

                $this->messageManager->addSuccessMessage(__('Successfully connected the mailbox!'));
            } else {
                $this->messageManager->addErrorMessage(__('Failed to obtain access token.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('OAuth2 token exchange failed: %1', $e->getMessage()));
        }

        return $this->redirectOpener('helpdesk/gateway/edit', ['id' => $gatewayId]);
    }

    /**
     * Return a raw response that navigates the opener to $path and closes the popup.
     * Falls back to a normal redirect when not opened in a popup.
     *
     * @param string $path
     * @param array  $params
     * @return \Magento\Framework\Controller\Result\Raw
     */
    private function redirectOpener(string $path, array $params = [])
    {
        $url = $this->_url->getUrl($path, $params);

        /** @var \Magento\Framework\Controller\Result\Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setContents(
            '<script type="text/javascript">'
            . 'if(window.opener&&!window.opener.closed){'
            . 'window.opener.location.href=' . json_encode($url) . ';'
            . 'window.close();'
            . '}else{'
            . 'window.location.href=' . json_encode($url) . ';'
            . '}'
            . '</script>'
        );

        return $result;
    }

    /**
     * Exchange authorization code for access and refresh tokens
     *
     * @param string $code
     * @param \Mirasvit\Helpdesk\Model\Gateway $gateway
     * @return array
     * @throws \Exception
     */
    protected function exchangeCodeForTokens($code, $gateway)
    {
        $tokenUrl = $this->provider->getTokenUrl(
            $gateway->getAuthorizationType(),
            $gateway->getTenant()
        );
        $redirectUri = $this->_url->getUrl(
            $this->provider->getCallbackRoute($gateway->getAuthorizationType()),
            ['_nosid' => true, '_nosecret' => true]
        );

        $postData = [
            'code' => $code,
            'client_id' => $gateway->getClientId(),
            'client_secret' => $gateway->getClientSecret(),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_POST, true);
        $this->curl->setOption(CURLOPT_POSTFIELDS, http_build_query($postData));
        $this->curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $this->curl->post($tokenUrl, $postData);
        $response = $this->curl->getBody();

        $tokens = json_decode($response, true);

        if (isset($tokens['error'])) {
            throw new \Exception($tokens['error_description'] ?? $tokens['error']);
        }

        return $tokens;
    }

    /**
     * Resolve the connected mailbox address from the token response.
     *
     * Microsoft: read it from the OIDC id_token claims — the Outlook-scoped access token cannot
     * call Microsoft Graph. Google: call the userinfo endpoint with the access token ("email").
     *
     * @param array  $tokens
     * @param string $authorizationType
     * @return string|null
     */
    protected function fetchAccountEmail($tokens, $authorizationType)
    {
        if ($this->provider->isMicrosoft($authorizationType)) {
            return $this->provider->extractEmailFromIdToken($tokens['id_token'] ?? null);
        }

        try {
            $accessToken = $tokens['access_token'] ?? '';
            $userinfoUrl = $this->provider->getUserInfoUrl($authorizationType);

            // Reset stale curl state left by the preceding POST (exchangeCodeForTokens).
            // setOption() stores into _curlUserOptions which is applied *after* makeRequest()
            // sets CURLOPT_HTTPGET, so a lingering CURLOPT_POST=true would turn this GET
            // into a POST and send the old token-exchange body to the userinfo endpoint.
            $this->curl->setOptions([]);
            $this->curl->setHeaders([]);
            $authHeader = 'Authorization';
            $this->curl->addHeader($authHeader, 'Bearer ' . $accessToken);

            $this->curl->get($userinfoUrl);
            $response = $this->curl->getBody();

            $userInfo = json_decode($response, true);

            foreach ($this->provider->getUserInfoEmailKeys($authorizationType) as $key) {
                if (!empty($userInfo[$key])) {
                    return $userInfo[$key];
                }
            }
        } catch (\Exception $e) {
            // Silently fail, email is optional
        }

        return null;
    }
}
