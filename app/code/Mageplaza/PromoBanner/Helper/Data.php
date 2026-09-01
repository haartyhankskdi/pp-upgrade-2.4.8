<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Helper;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\CollectionFactory;

/**
 * Class Data
 *
 * @package Mageplaza\PromoBanner\Helper
 */
class Data extends AbstractData
{
    const CONFIG_MODULE_PATH = 'mppromobanner';

    /**
     * @var CollectionFactory
     */
    protected $bannerCollection;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param FilterProvider $filterProvider
     * @param DateTime $date
     * @param HttpContext $httpContext
     * @param Session $checkoutSession
     * @param CollectionFactory $bannerCollection
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        FilterProvider $filterProvider,
        DateTime $date,
        HttpContext $httpContext,
        Session $checkoutSession,
        CollectionFactory $bannerCollection
    ) {
        $this->filterProvider   = $filterProvider;
        $this->date             = $date;
        $this->httpContext      = $httpContext;
        $this->checkoutSession  = $checkoutSession;
        $this->bannerCollection = $bannerCollection;

        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * @param null $store
     *
     * @return array
     */
    public function getCategoryList($store = null)
    {
        try {
            $categoryList = $this->unserialize($this->getConfigGeneral('promo_category', $store));
        } catch (Exception $e) {
            $categoryList = [];
        }

        $result = [];
        foreach ($categoryList as $key => $category) {
            $result[$key] = [
                'value' => $key,
                'label' => $category['category']
            ];
        }

        return $result;
    }

    /**
     * @return Collection
     */
    public function getPromoBannerCollection()
    {
        $customerGroup = $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
        /** @var Collection $collection */
        $collection = $this->bannerCollection->create();
        $collection->addActiveFilter($customerGroup, $this->getStoreId(), $this->date->date());

        return $collection;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        $id = 0;
        try {
            $id = $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage());
        }

        return $id;
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * @param $store
     *
     * @return mixed
     */
    public function showCloseButton($store = null)
    {
        return $this->getConfigGeneral('show_close_btn', $store);
    }

    /**
     * @param $store
     *
     * @return mixed
     */
    public function getAutoCloseTime($store = null)
    {
        return $this->getConfigGeneral('auto_close_time', $store);
    }

    /**
     * @param $store
     *
     * @return mixed
     */
    public function getAutoOpenTime($store = null)
    {
        return $this->getConfigGeneral('auto_reopen_time', $store);
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function showNav($store = null)
    {
        return $this->getModuleConfig('slider_setting/show_buttons', $store);
    }

    /**
     * @param $store
     *
     * @return mixed
     */
    public function getChangeTimeOut($store = null)
    {
        return $this->getModuleConfig('slider_setting/change_time', $store);
    }

    /**
     * @param string $code
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigPopup($code = '', $storeId = null)
    {
        $code = ($code !== '') ? '/' . $code : '';

        return $this->getConfigValue(static::CONFIG_MODULE_PATH . '/popup_setting' . $code, $storeId);
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getPopupResponsive($store = null)
    {
        return $this->getConfigPopup('popup_responsive', $store);
    }

    /**
     * @param string $code
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigFloating($code = '', $storeId = null)
    {
        $code = ($code !== '') ? '/' . $code : '';

        return $this->getConfigValue(static::CONFIG_MODULE_PATH . '/floating_setting' . $code, $storeId);
    }
}
