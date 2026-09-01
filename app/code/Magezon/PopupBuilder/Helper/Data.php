<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Store\Ui\Component\Listing\Column\Store\Options
     */
    protected $storeOptions;

    /**
     * @var \Magezon\Core\Helper\Data
     */
    protected $coreHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context                    $context
     * @param \Magento\Store\Model\StoreManagerInterface               $storeManager
     * @param \Magento\Store\Ui\Component\Listing\Column\Store\Options $storeOptions
     * @param \Magezon\Core\Helper\Data                                $coreHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Ui\Component\Listing\Column\Store\Options $storeOptions,
        \Magezon\Core\Helper\Data $coreHelper
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->storeOptions = $storeOptions;
        $this->coreHelper   = $coreHelper;
    }

    /**
     * @param  string $key
     * @param  null|int $store
     * @return null|string
     */
    public function getConfig($key, $store = null)
    {
        $store     = $this->storeManager->getStore($store);
        $websiteId = $store->getWebsiteId();
        $result    = $this->scopeConfig->getValue(
            'mgzpopupbuilder/' . $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $result;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->getConfig('general/enabled');
    }

    /**
     * Convert string to numbder
     */
    public function dataPreprocessing($data)
    {
        $fields = ['background_image1'];
        if (is_array($data)) {
            foreach ($data as $k => &$_row) {
                $_row = $this->coreHelper->unserialize($_row);
                if (is_numeric($_row)) {
                    $_row = (float) $_row;
                }
                if (is_array($_row)) {
                    $_row = $this->dataPreprocessing($_row);
                }
                if (is_string($k) && in_array($k, $fields)) {
                    $_row = $this->coreHelper->getImageUrl($_row);
                }
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getStores()
    {
        $stores = $this->storeOptions->toOptionArray();

        array_unshift($stores, [
            'label' => __('All Store Views'),
            'value' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
        ]);

        foreach ($stores as &$store) {
            if (is_array($store['value'])) {
                $store['optgroup'] = $store['value'];
                unset($store['value']);
            }

            if (isset($store['optgroup'])) {
                foreach ($store['optgroup'] as &$store1) {
                    if (is_array($store1['value'])) {
                        $store1['optgroup'] = $store1['value'];
                        unset($store1['value']);
                    }
                }
            }
        }

        return $stores;
    }
}
