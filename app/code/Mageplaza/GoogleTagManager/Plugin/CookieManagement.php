<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Plugin;

use Exception;
use Magento\Framework\App\FrontController;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\PageCache\Model\Cache\Type;
use Mageplaza\GoogleTagManager\Helper\Data;
use Zend_Cache;

/**
 * Class CookieManagement
 * @package Mageplaza\GoogleTagManager\Plugin
 */
class CookieManagement
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var Type
     */
    protected $cache;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;
    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;
    /**
     * @var ConfigInterface
     */
    private $sessionConfig;

    /**
     * CookieManagement constructor.
     *
     * @param Data $helperData
     * @param Type $cache
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        Data $helperData,
        Type $cache,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        ConfigInterface $sessionConfig
    ) {
        $this->helperData            = $helperData;
        $this->cache                 = $cache;
        $this->cookieManager         = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionConfig         = $sessionConfig;
    }

    /**
     * @param FrontController $subject
     * @param ResponseInterface|ResultInterface $result
     *
     * @return mixed
     * @throws InputException
     * @throws FailureToSendException
     */
    public function afterDispatch(FrontController $subject, $result)
    {
        try {
            $storeId = $this->helperData->getStoreId();
        } catch (Exception $e) {
            $storeId = null;
        }

        if ($this->helperData->isEnabled($storeId)
            && $this->helperData->getConfigAnalytics('secure_cookies', $storeId)
            && $this->cookieManager->getCookie('mpGTMCookie')) {
            $this->cache->clean(
                Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                ['mp_gtm_analytics', 'mp_gtm_analytics_head']
            );

            $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
            $cookieMetadata->setPath($this->sessionConfig->getCookiePath());
            $cookieMetadata->setDomain($this->sessionConfig->getCookieDomain());
            $cookieMetadata->setSecure($this->sessionConfig->getCookieSecure());
            $cookieMetadata->setHttpOnly($this->sessionConfig->getCookieHttpOnly());
            $this->cookieManager->deleteCookie('mpGTMCookie', $cookieMetadata);
        }

        return $result;
    }
}
