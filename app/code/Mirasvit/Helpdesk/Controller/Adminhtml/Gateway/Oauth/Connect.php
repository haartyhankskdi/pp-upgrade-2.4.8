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



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Gateway\Oauth;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Helpdesk\Helper\Oauth2Provider;
use Mirasvit\Helpdesk\Model\GatewayFactory;

class Connect extends Action
{
    const ADMIN_RESOURCE = 'Mirasvit_Helpdesk::helpdesk_gateway';

    /**
     * @var GatewayFactory
     */
    protected $gatewayFactory;

    /**
     * @var Oauth2Provider
     */
    protected $provider;

    /**
     * @param Context $context
     * @param GatewayFactory $gatewayFactory
     * @param Oauth2Provider $provider
     */
    public function __construct(
        Context $context,
        GatewayFactory $gatewayFactory,
        Oauth2Provider $provider
    ) {
        parent::__construct($context);
        $this->gatewayFactory = $gatewayFactory;
        $this->provider = $provider;
    }

    /**
     * Execute OAuth2 connect action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $gatewayId = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$gatewayId) {
            $this->messageManager->addErrorMessage(__('Gateway ID is required.'));
            return $resultRedirect->setPath('helpdesk/gateway/index');
        }

        $gateway = $this->gatewayFactory->create()->load($gatewayId);

        if (!$gateway->getId()) {
            $this->messageManager->addErrorMessage(__('Gateway not found.'));
            return $resultRedirect->setPath('helpdesk/gateway/index');
        }

        $clientId = $gateway->getClientId();
        $clientSecret = $gateway->getClientSecret();

        if (!$clientId || !$clientSecret) {
            $this->messageManager->addErrorMessage(__('Please configure OAuth2 Client ID and Secret first.'));
            return $resultRedirect->setPath('helpdesk/gateway/edit', ['id' => $gatewayId]);
        }

        // Generate CSRF token
        $csrfToken = bin2hex(random_bytes(16));
        $state = base64_encode($gatewayId . '|' . $csrfToken);

        // Store CSRF token in session
        $this->_session->setData('oauth2_csrf_token_' . $gatewayId, $csrfToken);

        // Build the provider-specific OAuth2 authorization URL (Google or Microsoft / Azure AD)
        $authType = $gateway->getAuthorizationType();

        $redirectUri = $this->_url->getUrl(
            $this->provider->getCallbackRoute($authType),
            ['_nosid' => true, '_nosecret' => true]
        );
        $authUrl  = $this->provider->getAuthorizeUrl($authType, $gateway->getTenant());
        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $this->provider->getScope($authType),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state
        ];

        $authorizationUrl = $authUrl . '?' . http_build_query($params);

        // Redirect to the provider OAuth consent screen
        return $resultRedirect->setUrl($authorizationUrl);
    }
}
