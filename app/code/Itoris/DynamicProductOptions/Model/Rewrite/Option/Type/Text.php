<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

namespace Itoris\DynamicProductOptions\Model\Rewrite\Option\Type;

class Text extends \Magento\Catalog\Model\Product\Option\Type\Text
{
    /** @var \Magento\Framework\ObjectManagerInterface|null  */
    protected $_objectManager = null;
    private $_formattedOptionValue = null;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Stdlib\StringUtils $string,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        $this->isEnabledDynamicOptions = $this->getItorisHelper()->isEnabledOnFrontend();
        parent::__construct($checkoutSession, $scopeConfig, $escaper, $string, $data);
    }

    public function validateUserValue($values) {
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            try {
                return parent::validateUserValue($values);
            } catch (\Exception $e) {
                $this->getItorisHelper()->addOptionError($this->getOption(), $this->getProduct(), $e->getMessage());
                //Mage::throwException($e->getMessage());
            }
        } else {
            return parent::validateUserValue($values);
        }
        return $this;
    }
    
    public function getOptionPrice($optionValue, $basePrice) {
        $price = parent::getOptionPrice($optionValue, $basePrice); //relative price
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            $product = $this->getOption()->getProduct();
            if ($product->getOptionsAbsolutePricing()) return $price;
            $dpoObj = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')->setStoreId($product->getStoreId())->load($product->getId(), 'product_id');
            if (!$dpoObj->getConfigId()) $dpoObj->setStoreId(0)->load($product->getId(), 'product_id');
            if ($dpoObj->getAbsolutePricing() == 1) { //absolute price
                $price -= $basePrice;
                $product->setOptionsAbsolutePricing(1);
            } else if ($dpoObj->getAbsolutePricing() == 2) { //fixed price
                $price = 0;
            } else $product->setOptionsAbsolutePricing(2);
        }
        return $price;
    }
    
    public function getOptionSku($optionValue, $skuDelimiter)
    {
        $sku = $this->getOption()->getSku();
        if (strpos($sku, '$1') !== false) $sku = str_replace('$1', trim($optionValue), $sku); //dynamic sku
        return $sku;
    }
    
    public function getFormattedOptionValue($optionValue) {
        if ($this->_formattedOptionValue === null) {
            if ($this->isEnabledDynamicOptions) {
                $this->_formattedOptionValue = $this->getEditableOptionValue($optionValue);
            } else {
                parent::getFormattedOptionValue($optionValue);
            }
        }
        return $this->_formattedOptionValue;
    }

    public function getEditableOptionValue($optionValue) {
        if (!$this->isEnabledDynamicOptions) {
            return parent::getEditableOptionValue($optionValue);
        }
        $sku = $this->getOptionSku($optionValue, '-');
        return $optionValue;//.($sku ? ' ('.__('SKU').': '.$sku.')' : '');
    }
    
    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    public function getItorisHelper(){
        return $this->_objectManager->get('Itoris\DynamicProductOptions\Helper\Data');
    }

}