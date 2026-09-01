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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Block\Contact;

use Mirasvit\Helpdesk\Model\DepartmentFactory;
use Mirasvit\Helpdesk\Model\PriorityFactory;
use Magento\Framework\View\Element\Template;
use Mirasvit\Helpdesk\Model\Config;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Helpdesk\Helper\Field as FieldHelper;
use Magento\Framework\Registry;
use Magento\Framework\File\Size;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Http\Context as HttpContext;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ContactUsForm extends \Magento\Contact\Block\ContactForm
{
    protected $priorityFactory;

    protected $departmentFactory;

    protected $config;

    protected $fieldHelper;

    protected $moduleManager;

    protected $registry;

    protected $fileSize;

    protected $customerFactory;

    protected $customerSession;

    protected $context;

    protected $isAjax = false;

    private $httpContext;

    /**
     * @param boolean $isAjax
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        PriorityFactory $priorityFactory,
        DepartmentFactory $departmentFactory,
        Config $config,
        FieldHelper $fieldHelper,
        ModuleManager $moduleManager,
        Registry $registry,
        Size $fileSize,
        CustomerFactory $customerFactory,
        HttpContext $httpContext,
        Context $context,
        $isAjax = false
    ) {
        $this->priorityFactory = $priorityFactory;
        $this->departmentFactory = $departmentFactory;
        $this->config = $config;
        $this->fieldHelper = $fieldHelper;
        $this->moduleManager = $moduleManager;
        $this->registry = $registry;
        $this->fileSize = $fileSize;
        $this->customerFactory = $customerFactory;
        $this->httpContext = $httpContext;
        $this->context = $context;
        $this->isAjax = $isAjax;
        //we should use object manager to get customerId when session enabled
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->customerSession = $objectManager->create('Magento\Customer\Model\Session');

        parent::__construct($context);

        $this->_isScopePrivate = false;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        if ($this->getConfig()->getGeneralContactUsIsActive() && $this->getNameInLayout() == 'contactForm') {
            return 'Mirasvit_Helpdesk::contacts/form.phtml';
        } else {
            return parent::getTemplate();
        }
    }

    /**
     * @return bool
     */
    public function isKbEnabled()
    {
        return $this->moduleManager->isEnabled('Mirasvit_Kb') && $this->getConfig()->getContactFormIsActiveKb();
    }

    /**
     * @return \Mirasvit\Helpdesk\Block\Contact\ContactUsForm
     */
    public function getKbBlock()
    {
        return $this->_layout->createBlock('Mirasvit\Helpdesk\Block\Contact\ContactUsForm')
            ->setTemplate('Mirasvit_Helpdesk::contact/form/kb.phtml');
    }

    /**
     * @return bool
     */
    public function isAttachmentEnabled()
    {
        return $this->getConfig()->getContactFormIsActiveAttachment();
    }

    /**
     * @return float
     */
    public function getAttachmentSize()
    {
        return $this->fileSize->getMaxFileSizeInMb();
    }

    /**
     * @return \Magento\Customer\Model\Customer|bool
     */
    public function getCustomer()
    {
        $customer = $this->customerFactory->create()->load($this->customerSession->getCustomerId());
        if ($customer->getId() > 0) {
            return $customer;
        } elseif ($this->httpContext->getValue('customer_logged_in')) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        $customer = $this->getCustomer();

        if (is_object($customer)) {
            return $customer->getName();
        }

        return '';
    }

    /**
     * @return string
     */
    public function getCustomerEmail()
    {
        $customer = $this->getCustomer();

        if (is_object($customer)) {
            return $customer->getEmail();
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        $isHTTPS = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            || !empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443;

        return $isHTTPS;
    }

    /**
     * @return string
     */
    public function getContactUrl()
    {
        $this->customerSession->setFeedbackUrl($this->_urlBuilder->getCurrentUrl());

        if ($this->isKbEnabled()) {
            return $this->getKbUrl();
        } else {
            return $this->getFormUrl();
        }
    }

    /**
     * @return string
     */
    public function getFormUrl()
    {
        return $this->context->getUrlBuilder()->getUrl(
            'helpdesk/contact/form',
            ['_secure' => $this->isSecure(), 's' => $this->getSearchQuery(), 'isAjax' => 1]
        );
    }

    /**
     * @return string
     */
    public function getPostUrl()
    {
        return $this->context->getUrlBuilder()->getUrl(
            'helpdesk/contact/postmessage',
            ['_secure' => $this->isSecure()]
        );
    }

    /**
     * @return string
     */
    public function getKbResultUrl()
    {
        return $this->context->getUrlBuilder()
            ->getUrl('helpdesk/contact/kb', ['_secure' => $this->isSecure()]);
    }

    /**
     * @return string
     */
    public function getKbUrl()
    {
        return $this->context->getUrlBuilder()->getUrl(
            'helpdesk/contact/kb',
            ['_secure' => $this->isSecure(), 's' => $this->getSearchQuery()]
        );
    }

    /**
     * @return string
     */
    public function getAllResultsUrl()
    {
        return $this->context->getUrlBuilder()->getUrl(
            'kb/article/s',
            ['_secure' => $this->isSecure(), 's' => $this->getSearchQuery()]
        );
    }

    /**
     * @return object
     */
    public function getSearchQuery()
    {
        return $this->registry->registry('search_query');
    }

    /**
     * @return object
     */
    public function getArticleCollection()
    {
        return $this->registry->registry('search_result');
    }

    /**
     * @return object
     */
    public function isRatingEnabled()
    {
        return $this->config->getGeneralIsRatingEnabled();
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Field[]|\Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    public function getCustomFields()
    {
        $collection = $this->fieldHelper->getContactFormCollection();

        return $collection;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Field $field
     * @return string
     */
    public function getInputHtml($field)
    {
        return $this->fieldHelper->getInputHtml($field);
    }

    /**
     * @return object
     */
    public function getIsAllowPriority()
    {
        return $this->getConfig()->getContactFormIsAllowPriority();
    }

    /**
     * @return object
     */
    public function getIsAllowDepartment()
    {
        return $this->getConfig()->getContactFormIsAllowDepartment();
    }

    /**
     * @return bool
     */
    public function isShowCaptcha()
    {
        return $this->getConfig()->getExtendedSettingsShowCaptcha($this->context->getStoreManager()->getStore());
    }

    /**
     * @return object
     */
    public function getPriorityCollection()
    {
        return $this->priorityFactory->create()->getPreparedCollection($this->context->getStoreManager()->getStore());
    }

    /**
     * @return object
     */
    public function getDepartmentCollection()
    {
        return $this->departmentFactory->create()->getPreparedCollection($this->context->getStoreManager()->getStore())
            ->addFieldToFilter('is_show_in_frontend', true);
    }

    /**
     * @return string
     */
    public function getHelpText()
    {
        return nl2br($this->getConfig()->getExtendedSettingsHelpText($this->context->getStoreManager()->getStore()));
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->isAjax) {
            return '';
        }

        $captchaBlock = $this->getLayout()->getBlock('hdmx-msp-recaptcha');
        if ($this->config->getExtendedSettingsShowCaptcha() && $captchaBlock &&
            get_class($captchaBlock) != 'Mirasvit\Helpdesk\Block\MspRecaptcha\Frontend\ReCaptcha\RecaptchaPopup' &&
            $this->getType() == 'Mirasvit\Helpdesk\Block\Contact\PopupForm'
        ) {
            return '';
        }

        return parent::_toHtml();
    }
}
