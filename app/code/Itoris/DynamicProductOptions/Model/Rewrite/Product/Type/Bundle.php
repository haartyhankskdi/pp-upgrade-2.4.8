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

namespace Itoris\DynamicProductOptions\Model\Rewrite\Product\Type;

class Bundle extends \Magento\Bundle\Model\Product\Type
{
    public function checkProductBuyState($product = null) {
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            $this->getItorisProductTypeHelper()->checkDynamicOptions($product);

            foreach ($product->getProductOptionsCollection() as $option) {
                if ($option->getIsRequire() && !$product->getData('skip_required_option' . $option->getId())) {
                    $customOption = $product->getCustomOption('option_' . $option->getId());
                    if (!$customOption || strlen($customOption->getValue()) == 0) {
                        $product->setSkipCheckRequiredOption(true);
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('The product has required options.')
                        );
                    }
                }
            }
            return $this;
        }
        return parent::checkProductBuyState($product);
    }
    
    public function getProduct($product = null)
    {
        if (is_object($product)) {
            return $product;
        }
        return $this->getObjectManager()->create('Magento\Catalog\Model\Product');
    }

    protected function _prepareOptions(\Magento\Framework\DataObject $buyRequest, $product, $processMode) {
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            $optionValues = [];
            $helper = $this->getItorisProductTypeHelper();
            foreach ($product->getProductOptionsCollection() as $_option) {
                if ($_option->getType() == 'file') {
                    continue;
                }
                $group = $_option->groupFactory($_option->getType())
                    ->setOption($_option)
                    ->setProduct($this->getProduct($product))
                    ->setRequest($buyRequest)
                    ->setProcessMode('lite')
                    ->validateUserValue($buyRequest->getOptions());

                $preparedValue = $group->prepareForCart();
                if ($preparedValue !== null) {
                    $optionValues[$_option->getId()] = $helper->prepareOptionValue($_option, $preparedValue);
                }
            }
            $this->getItorisProductTypeHelper()->checkDynamicOptions($product, $optionValues);

        }
        return parent::_prepareOptions($buyRequest, $product, $processMode);
    }
    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    public function getItorisHelper(){
        return $this->getObjectManager()->get('Itoris\DynamicProductOptions\Helper\Data');
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\ProductType
     */
    public function getItorisProductTypeHelper(){
        return $this->getObjectManager()->create('Itoris\DynamicProductOptions\Helper\ProductType');
    }
    
    public function getObjectManager() {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }
}