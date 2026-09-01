<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\AbandonedCart\Block\Adminhtml\Customer\Cart;

class Details extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    const ADMIN_EMAIL_ID = "webkul_abandoned_cart/abandoned_cart_mail_configuration/admin_email_id";
    const ADMIN_NAME = "webkul_abandoned_cart/abandoned_cart_mail_configuration/admin_name_in_email";
    
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Registry $registry
     * @param array $data = []
     **/
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /*
     * changes the default buttons at the layout
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Webkul_AbandonedCart';
        $this->_controller = 'adminhtml_customer_cart_details';

        parent::_construct();
        $this->buttonList->remove('save');
        $this->buttonList->add(
            'my_back',
            [
                'label' =>  'Back',
                'onclick'   => 'setLocation(\'' . $this->getUrl('abandonedcart/customer/customerlist') . '\')',
                'class'     =>  'back'
            ],
            100
        );
        $this->buttonList->add('abandonedCartMail', [
            'label'   => __('Send Mail'),
            'class'   => 'primary'
        ]);

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
    }
    
    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * @return cartId
     */
    public function getcartId()
    {
        return $cartId = $this->getRequest()->getParam('cart_id');
    }

    /**
     * Get Admin name from Scopeconfig
     *
     * @return string
     */
    public function getAdminNameFromConfig()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::ADMIN_NAME);
    }

    /**
     * Get Admin email from Scopeconfig
     *
     * @return string
     */
    public function getEmailFromConfig()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::ADMIN_EMAIL_ID);
    }
}
