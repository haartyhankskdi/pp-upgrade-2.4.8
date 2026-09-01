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

namespace Itoris\DynamicProductOptions\Observers;

use Magento\Framework\Event\ObserverInterface;

class ChangeItemWeight implements ObserverInterface
{
    protected $isEnabledFlag = false;
    /**
     * @var \Magento\Framework\ObjectManagerInterface|null
     */
    protected $_objectManager = null;
    /**
     * @var \Magento\Framework\App\RequestInterface|null
     */
    protected $_request = null;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_request = $request;
        try {
            $this->isEnabledFlag = $this->_objectManager->get('Itoris\DynamicProductOptions\Helper\Data')->getSettings(true)->getEnabled();
        } catch (\Exception $e) {/** save store model */}
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {

        if (!$this->isEnabledFlag) {
            return $this;
        }
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $observer->getQuoteItem();
        if (!$quoteItem || !$quoteItem->getProductId() || !$quoteItem->getQuote()) {
            return $this;
        }
        $product = $observer->getProduct();
        $buyRequest = $quoteItem->getBuyRequest();
        $options = $buyRequest->getOptions();

        //checking for absolute weight
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('read');
        $row = $con->fetchRow("select `absolute_weight`, `absolute_sku`, `sku_order` from {$res->getTableName('itoris_dynamicproductoptions_options')} where `product_id`={$product->getId()} and (`store_id`={$product->getStoreId()} or `store_id`=0) order by `store_id` desc");
        if (empty($row)) return $this;
        $isAbsoluteWeight = (int) $row['absolute_weight'];
        $isAbsoluteSku = (int) $row['absolute_sku'];

        if ($isAbsoluteWeight == 1) $quoteItem->setWeight(0); //absolute weight
        if ($isAbsoluteSku == 1) { //absolute sku
            $productOrigSku = $con->fetchOne("select `sku` from {$res->getTableName('catalog_product_entity')} where `entity_id`={$product->getId()}");
            //$quoteItem->setSku(str_ireplace($productOrigSku.'-', '', $quoteItem->getSku()));
            $quoteItem->setSku(substr($quoteItem->getSku(), strlen($productOrigSku) + 1));
        } else if ($isAbsoluteSku == 2) { //fixed sku
            $productOrigSku = $con->fetchOne("select `sku` from {$res->getTableName('catalog_product_entity')} where `entity_id`={$product->getId()}");
            $quoteItem->setSku($productOrigSku);
        } else if ($isAbsoluteSku == 3 && $row['sku_order']) { //custom sku order
            $finalSku = $row['sku_order'];
            $productOrigSku = $con->fetchOne("select `sku` from {$res->getTableName('catalog_product_entity')} where `entity_id`={$product->getId()}");
            $finalSku = str_ireplace('{base}', $productOrigSku, $finalSku);
            
            foreach ($options as $optionId => $option) {
                $productOption = $this->_objectManager->create('Magento\Catalog\Model\Product\Option')->load($optionId);
                $dpoOption = json_decode($con->fetchOne("select `configuration` from {$res->getTableName('itoris_dynamicproductoptions_option')} where `orig_option_id`={$optionId} and `store_id`=0"), true);
                $internalId = isset($dpoOption['internal_id']) ? (int) $dpoOption['internal_id'] : 0;
                if ($productOption->getGroupByType($productOption->getType()) == \Magento\Catalog\Model\Product\Option::OPTION_GROUP_SELECT) {
                    if (!is_array($option)) $option = [$option];
                    $_sku = [];
                    foreach ($option as $optionValueId) {
                        $productOptionValue = $this->_objectManager->create('Magento\Catalog\Model\Product\Option\Value')->load($optionValueId);
                        $_sku[] = $productOptionValue->getSku();
                    }
                    $finalSku = str_ireplace('{f'.$internalId.'}', implode('-', $_sku), $finalSku);
                } else {
                    $finalSku = str_ireplace('{f'.$internalId.'}', $productOption->getSku(), $finalSku);
                }
            }
            $finalSku = preg_replace('/\{(.*)}/U', '', $finalSku);
            $finalSku = preg_replace('/\-+/', '-', $finalSku);
            $finalSku = trim($finalSku, '-');
            $quoteItem->setSku($finalSku);
        }
        
        if (is_array($options) && $isAbsoluteWeight != 2) { //not fixed weight
            foreach ($options as $optionId => $option) {
                /** @var  $productOption \Magento\Catalog\Model\Product\Option */
                $productOption = $this->_objectManager->create('Magento\Catalog\Model\Product\Option')->load($optionId);
                $optionType = $productOption->getType();
                if ($productOption->getGroupByType($optionType) != \Magento\Catalog\Model\Product\Option::OPTION_GROUP_SELECT) {
                    continue;
                }
                if (!is_array($option)) {
                    $option = [$option];
                }
                foreach ($option as $optionValueId) {
                    if (!$optionValueId) continue;
                    $dynamicValue = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value')->load($optionValueId, 'orig_value_id');
                    $valueConfiguration = $dynamicValue->getConfiguration();
                    if ($valueConfiguration) {
                        $valueConfiguration = \Zend_Json::decode($valueConfiguration);
                        if (isset($valueConfiguration['sku_is_product_id']) && $valueConfiguration['sku_is_product_id']) {
                            /** @var  $valueModel \Magento\Catalog\Model\Product\Option\Value*/
                            //$valueModel = $this->_objectManager->create('Magento\Catalog\Model\Product\Option\Value')->load($optionValueId);
                            /** @var $valueProduct \Magento\Catalog\Model\Product */
                            $valueProduct = $this->_objectManager->create('Magento\Catalog\Model\Product')->load((int)$valueConfiguration['sku']);

                            $optionsQty = $buyRequest->getOptionsQty();
                            $optionQty = 1;
                            if (is_array($optionsQty)) {
                                if (in_array($productOption->getType(), ['radio', 'drop_down'])) {
                                    if (isset($optionsQty[$productOption->getId()])) {
                                        $optionQty = (int)$optionsQty[$productOption->getId()];
                                    }
                                } else {
                                    if (isset($optionsQty[$productOption->getId()][$optionValueId])) {
                                        $optionQty = (int)$optionsQty[$productOption->getId()][$optionValueId];
                                    }
                                }
                            }
                            $optionProductWeight = $optionQty * $valueProduct->getWeight();
                            $quoteItem->setWeight($quoteItem->getWeight() + $optionProductWeight);
                        }
                        elseif(isset($valueConfiguration['weight']) && $valueConfiguration['weight']){
                            $quoteItem->setWeight($quoteItem->getWeight() + $valueConfiguration['weight']);
                        }
                    }
                }
            }
        }
    }
}