<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\Theme\Block;

class HomePage extends \Magento\Framework\View\Element\Template
{
    // This is Uploaded Image path
    const XML_UPLOADED_PATH = "custom_theme/homepage/";
    // This is for section one
    const XML_SECTION_ONE = "homepage/section_one/";
    // This is for section two
    const XML_SECTION_TWO = "homepage/section_two/";
    /**
     * 
     */
    const XML_SECTION_WCFY = "homepage/section_we_care_for_you/";

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /**
     * !This method is vulnerable - Cannot be get User directly to .phtml file
     * Ability to get System Config Data
     *
     * @param String $xmlPath
     * @return void
     */
    public function getConfigData($xmlPath)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue($xmlPath, $storeScope);
    }

    /**
     * Ability to get System Config Data for Section One (Offer Banner)
     *
     * @param string $xmlPath
     * @return void|String|MIXED
     */
    public function getConfigDataSectionOne($xmlPath = '')
    {
        if(empty($xmlPath)) return "";
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_SECTION_ONE.$xmlPath, $storeScope);
    }

    /**
     * Ability to get System Config Data for Section Two (Category)
     *
     * @param string $xmlPath
     * @return void|String|MIXED
     */
    public function getConfigDataSectionTwo($xmlPath = '')
    {
        if(empty($xmlPath)) return "";
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_SECTION_TWO.$xmlPath, $storeScope);
    }

    /**
     * Ability to get System Config Data for We Care For You Section
     *
     * @param string $xmlPath
     * @return void|String|MIXED
     */
    public function getConfigDataWeCareForYou($xmlPath = '')
    {
        if(empty($xmlPath)) return "";
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_SECTION_WCFY.$xmlPath, $storeScope);
    }

    /**
     * Method use for getting media url
     *
     * @return String
     */
    public function getMediaUrl()
    {
        $currentStore = $this->_storeManager->getStore();
        return $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Path where Image get store from system config
     *
     * @return String
     */
    public function getSectionImagePath()
    {
        return self::XML_UPLOADED_PATH;
    }
}

