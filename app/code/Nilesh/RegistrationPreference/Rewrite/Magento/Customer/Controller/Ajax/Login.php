<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\RegistrationPreference\Rewrite\Magento\Customer\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\App\ObjectManager;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
/* Custom */
use Nilesh\Theme\Helper\CartButton;
use Nilesh\Theme\Helper\RememberMe as CustomCooike;

/**
 * Login controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Login extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Framework\Json\Helper\Data $helper
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var CartButton
     */
    private $_cartHelper;

    /**
    * @var Nilesh\RememberMe\Helper\Data
    */
    private $getCookiedata;

    /**
     *  @var \Magento\Store\Model\StoreManagerInterface
    */
    private $storeManager;

    /**
     * Initialize Login controller
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param AccountManagementInterface $customerAccountManagement
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        CustomCooike $getCookiedata,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CartButton $cartHelper,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Json\Helper\Data $helper,
        AccountManagementInterface $customerAccountManagement,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        CookieManagerInterface $cookieManager = null,
        CookieMetadataFactory $cookieMetadataFactory = null
    ) {
        parent::__construct($context);
        $this->getCookiedata = $getCookiedata;
        $this->storeManager = $storeManager;
        $this->_cartHelper = $cartHelper;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->cookieManager = $cookieManager ?:
            ObjectManager::getInstance()->get(CookieManagerInterface::class);
        $this->cookieMetadataFactory = $cookieMetadataFactory ?:
            ObjectManager::getInstance()->get(CookieMetadataFactory::class);
    }

    /**
     * Get account redirect.
     *
     * @deprecated 100.0.10
     * @return AccountRedirect
     */
    protected function getAccountRedirect()
    {
        if (!is_object($this->accountRedirect)) {
            $this->accountRedirect = ObjectManager::getInstance()->get(AccountRedirect::class);
        }
        return $this->accountRedirect;
    }

    /**
     * Account redirect setter for unit tests.
     *
     * @deprecated 100.0.10
     * @param AccountRedirect $value
     * @return void
     */
    public function setAccountRedirect($value)
    {
        $this->accountRedirect = $value;
    }

    /**
     * Initializes config dependency.
     *
     * @deprecated 100.0.10
     * @return ScopeConfigInterface
     */
    protected function getScopeConfig()
    {
        if (!is_object($this->scopeConfig)) {
            $this->scopeConfig = ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        }
        return $this->scopeConfig;
    }

    /**
     * Sets config dependency.
     *
     * @deprecated 100.0.10
     * @param ScopeConfigInterface $value
     * @return void
     */
    public function setScopeConfig($value)
    {
        $this->scopeConfig = $value;
    }

    public function cartIsNotEmpty()
    {
        $cart = ObjectManager::getInstance()->get(\Magento\Checkout\Model\Cart::class);
        $items = $cart->getQuote()->getAllItems();
        $totalItems= count($items);
        if($totalItems > 0){
            return true;
        }
        return false;
    }

    /**
     * Login registered users and initiate a session.
     *
     * Expects a POST. ex for JSON {"username":"user@magento.com", "password":"userpassword"}
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $credentials = null;
        $httpBadRequestCode = 400;

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        try {
            $credentials = $this->helper->jsonDecode($this->getRequest()->getContent());
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$credentials || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        if (array_key_exists('rememberme',$credentials)) {
            $logindetails = array('username' => $credentials['username'], 'remchkbox' => 1);
            $logindetails = json_encode($logindetails);
            $this->getCookiedata->set($logindetails, $this->getCookiedata->getCookielifetime());
        } else {
            $this->getCookiedata->delete('remember');
        }

        $response = [
            'errors' => false,
            'message' => __('Login successful.')
        ];
        try {
            $customer = $this->customerAccountManagement->authenticate(
                $credentials['username'],
                $credentials['password']
            );
            $this->customerSession->setCustomerDataAsLoggedIn($customer);
            $redirectRoute = $this->getAccountRedirect()->getRedirectCookie();
            if ($this->cookieManager->getCookie('mage-cache-sessid')) {
                $metadata = $this->cookieMetadataFactory->createCookieMetadata();
                $metadata->setPath('/');
                $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
            }
            if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectRoute) {
                // $response['redirectUrl'] = $this->_redirect->success($redirectRoute);
                $this->getAccountRedirect()->clearRedirectCookie();
            }
            $getItemNeedGQ = $this->_cartHelper->getCartNeedGeneralQuestion();
            if($getItemNeedGQ && ($this->_cartHelper->getNeedOfGQ() == 0) && $this->_cartHelper->isLogin()){
                $response['redirectUrl'] = $this->_redirect->success($this->storeManager->getStore()->getUrl("customer/general/question", array("referer"=>base64_encode($this->storeManager->getStore()->getUrl("onestepcheckout/index/index")))));
            }
            elseif($this->cartIsNotEmpty()){
                // $response['redirectUrl'] = $this->_redirect->success($this->storeManager->getStore()->getUrl('onestepcheckout'));
                $response['redirectUrl'] = $this->_redirect->success($this->storeManager->getStore()->getUrl('checkout/cart')."?confirm=true");
            }else{
                $response['redirectUrl'] = $this->_redirect->success($redirectRoute);
            }
        } catch (LocalizedException $e) {
            // Invalid login or password.
            $message = $e->getMessage();
            if (strpos($message, 'nvalid login or password') > 0) {
                $message = __('Invalid username or password. You may have not registered with us. Please register.');
            }
            $response = [
                'errors' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'message' => __('Invalid username or password. You may have not registered with us. Please register.'),
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
