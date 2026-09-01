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

class UpdateOrderInventory implements ObserverInterface
{
    protected $isEnabledFlag = false;
    /**
     * @var \Magento\Framework\ObjectManagerInterface|null
     */
    protected $_objectManager = null;
    /**
     * @var \Magento\Framework\App\RequestInterface|null
     */
      
    protected $_checkoutSession;
    
    protected $_request = null;
    
    protected $scopeConfig;
    protected $isPriceAlreadyInclTax;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_request = $request;
        $this->_checkoutSession = $objectManager->get('Magento\Checkout\Model\Session');
        $this->scopeConfig = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $this->isPriceAlreadyInclTax = (int) $this->scopeConfig->getValue('tax/calculation/price_includes_tax', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        try {
            $this->isEnabledFlag = $this->_objectManager->get('Itoris\DynamicProductOptions\Helper\Data')->getSettings(true)->getEnabled();
        } catch (\Exception $e) {/** save store model */}
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        if (!$this->isEnabledFlag) {
            return $this;
        }

        $eventName = $observer->getEvent()->getName();
        $post = $this->_request->getParams();
        $isQuote = false;

        if ($eventName == 'controller_action_predispatch_paypal_express_return') {
            $orderItems = $this->_checkoutSession->getQuote()->getAllItems();
            $isQuote = true;
        } else if ($eventName == 'sales_order_creditmemo_refund') {
            $orderItems = $observer->getEvent()->getCreditmemo()->getOrder()->getAllItems();
        } else {
            $orderItems = $observer->getEvent()->getOrder()->getAllItems();
        }

        $taxHelper = $this->_objectManager->get('Magento\Catalog\Helper\Data');
        
        $needsOrderReload = false;
        foreach ($orderItems as $orderItem) {
            if ($isQuote) {
                $productOptions = $orderItem->getProduct()->getTypeInstance(true)->getOrderOptions($orderItem->getProduct());
                $qty = $orderItem->getQty();
            } else {
                $productOptions = $orderItem->getProductOptions();
                $qty = $orderItem->getQtyOrdered();
            }
            
            if (!isset($productOptions['options'])) continue;

            foreach ($productOptions['options'] as $optionKey => $option) {
                switch ($option['option_type']) {
                    case 'drop_down':
                    case 'radio':
                    case 'checkbox':
                    case 'multiple':
                        $optionTypeIds = explode(',', $option['option_value']);

                        foreach ($optionTypeIds as $optionTypeId) {
                            /** @var  $dynamicValue \Itoris\DynamicProductOptions\Model\Option\Value */
                            $dynamicValue = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value')->load($optionTypeId, 'orig_value_id');
                            $valueConfiguration = $dynamicValue->getConfiguration();
                            if ($valueConfiguration) {
                                $valueConfiguration = \Zend_Json::decode($valueConfiguration);
                                if (isset($valueConfiguration['sku_is_product_id']) && $valueConfiguration['sku_is_product_id']) {
                                    /** @var  $valueModel \Magento\Catalog\Model\Product\Option\Value */
                                    //$valueModel = $this->_objectManager->create('Magento\Catalog\Model\Product\Option\Value')->load($optionTypeId);
                                    /** @var $valueProduct \Magento\Catalog\Model\Product */
                                    $valueProduct = $this->_objectManager->create('Magento\Catalog\Model\Product')->load((int)$valueConfiguration['sku']);
                                    
                                    $buyRequest = $orderItem->getBuyRequest();
                                    $optionsQty = $buyRequest->getOptionsQty();
                                    $optionQty = 1;
                                    if (is_array($optionsQty)) {
                                        if (in_array($option['option_type'], ['radio', 'drop_down'])) {
                                            if (isset($optionsQty[$option['option_id']])) {
                                                $optionQty = (int)$optionsQty[$option['option_id']];
                                            }
                                        } else {
                                            if (isset($optionsQty[$option['option_id']][$optionTypeId])) {
                                                $optionQty = (int)$optionsQty[$option['option_id']][$optionTypeId];
                                            }
                                        }
                                    }
                                    
                                    $newOrderItem = false;
                                    if ($valueProduct->getId()) {
                                        if (isset($valueConfiguration['separate_cart_item']) && $valueConfiguration['separate_cart_item']
                                                && $eventName != 'order_cancel_after' && $eventName != 'sales_order_creditmemo_refund') {
                                            //creating new order item

                                            $productPrice = $valueProduct->getPrice();
                                            if (!isset($valueConfiguration['sku_is_product_id_linked']) || !$valueConfiguration['sku_is_product_id_linked']) {
                                                //check if option price overrides the product price
                                                $productPrice = $valueConfiguration['price_type'] == 'fixed' ? (float)$valueConfiguration['price'] : $orderItem->getOriginalPrice() / 100 * $valueConfiguration['price'];
                                                if (isset($valueConfiguration['special_price']) && floatval($valueConfiguration['special_price']) > 0) $productPrice = (float)$valueConfiguration['special_price'];
                                                if (isset($valueConfiguration['special_price'])) {
                                                    $tierPrices = @json_decode($valueConfiguration['tier_price'], true);
                                                    if (is_array($tierPrices)) foreach($tierPrices as $tierPrice) {
                                                        if ($optionQty >= $tierPrice['qty']) {
                                                            $_tierPrice = $tierPrice['price_type'] == 'fixed' ? (float)$tierPrice['price'] : $orderItem->getOriginalPrice() / 100 * $tierPrice['price'];
                                                            if ($productPrice > $_tierPrice) $productPrice = $_tierPrice;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            //if (!$isQuote) $productPrice = $taxHelper->getTaxPrice($valueProduct, $productPrice, false); //print_r($productPrice.' ');
                                            
                                            if ($orderItem->getPrice() > 0) {
                                                $taxRate = $orderItem->getBasePriceInclTax() / $orderItem->getBasePrice();
                                                if ($this->isPriceAlreadyInclTax) $productPrice /= $taxRate;
                                                $conversionRate = $orderItem->getPriceInclTax() / $orderItem->getBasePriceInclTax();
                                                $correctionAmount = $orderItem->getBasePrice() - $productPrice * $optionQty;
                                                $correctionRate = $correctionAmount / $orderItem->getBasePrice();
                                            } else {
                                                $taxRate = 1;
                                                $conversionRate = 1;
                                                $correctionRate = 1;
                                            }
                                            
                                            //print_r($productPrice.':'.$taxRate.':'.$conversionRate.':'.$correctionAmount.':'.$correctionRate."\n");
                                            
                                            $fieldsToUpdate = ['price', 'base_price', 'tax_amount', 'base_tax_amount', 'discount_amount', 'base_discount_amount',
                                                                'row_total', 'base_row_total', 'price_incl_tax', 'base_price_incl_tax', 'row_total_incl_tax', 'base_row_total_incl_tax'];
                                            
                                            $buyRequestArray = $productOptions;
                                            if ($isQuote) {
                                                $newOrderItem = $this->_objectManager->create('Magento\Quote\Model\Quote\Item');
                                            } else {
                                                $newOrderItem = $this->_objectManager->create('Magento\Sales\Model\Order\Item');
                                            }
                                            $newOrderItem->setOrderId($orderItem->getOrderId());
                                            $newOrderItem->setQuoteId($orderItem->getQuoteId());
                                            $newOrderItem->setStoreId($orderItem->getStoreId());
                                            $newOrderItem->setProduct($orderItem->getProduct());
                                            $newOrderItem->setProductId($valueProduct->getId());
                                            $newOrderItem->setProductType($valueProduct->getTypeId());
                                            $newOrderItem->setWeight($valueProduct->getWeight());
                                            $newOrderItem->setRowWeight($valueProduct->getWeight() * $qty * $optionQty);
                                            $newOrderItem->setSku($valueProduct->getSku());
                                            $newOrderItem->setTaxPercent($orderItem->getTaxPercent());
                                            $newOrderItem->setName($valueProduct->getName());
                                            $newOrderItem->setIsVirtual($valueProduct->getIsVirtual());
                                            $newOrderItem->setQtyOrdered($qty * $optionQty)->setQty($qty * $optionQty);
                                            $newOrderItem->setProductOptions(['info_buyRequest' => [
                                                'uenc' => isset($buyRequestArray['info_buyRequest']['uenc']) ? $buyRequestArray['info_buyRequest']['uenc'] : '',
                                                'product' => $valueProduct->getId(),
                                                'qty' => $qty * $optionQty
                                            ]]);
                                            $newOrderItem->setOriginalPrice($valueProduct->getPrice() * $conversionRate)->setBaseOriginalPrice($valueProduct->getPrice());
                                            
                                            if ($isQuote) {
                                                $newOrderItem->setCustomPrice($productPrice * $taxRate * $conversionRate)->setOriginalCustomPrice($productPrice * $taxRate * $conversionRate);
                                                //print_r($productPrice.' '.$taxRate.' '.$conversionRate."\n");
                                            }
                                            foreach($fieldsToUpdate as $field) {
                                                $newOrderItem->setData($field, (float)$orderItem->getData($field) - (float)$orderItem->getData($field) * $correctionRate);
                                            }
                                            if ($newOrderItem->getQtyOrdered() > 0) {
                                                $newOrderItem->setPrice($newOrderItem->getPrice() / $newOrderItem->getQtyOrdered())->setBasePrice($newOrderItem->getPrice() / $newOrderItem->getQtyOrdered());
                                            }
                                            
                                            $newOrderItem->save();
                                            
                                            //updating parent item
                                            $orderItem->setSku(str_ireplace(['-'.$newOrderItem->getSku(), '--'], ['', '-'], $orderItem->getSku()));
                                            $orderItem->setWeight($orderItem->getWeight() - (float)$newOrderItem->getWeight());
                                            $orderItem->setRowWeight($orderItem->getRowWeight() - (float)$newOrderItem->getRowWeight());
                                            
                                            foreach($fieldsToUpdate as $field) {
                                                $orderItem->setData($field, (float)$orderItem->getData($field) * $correctionRate);
                                                //print_r($field.':'.$orderItem->getData($field)."\n");
                                            }
                                            
                                            //print_r("\n\n");
                                            
                                            if ($isQuote) {//print_R($productOptions); exit;
                                                //$orderItem->setCustomPrice($orderItem->getPriceInclTax())->setOriginalCustomPrice($orderItem->getPriceInclTax());
                                                $_options = $orderItem->getOptions();
                                                //foreach($_options as $_option) print_r($_option->getData()); exit;
                                                foreach($_options as $_option) {
                                                    if ($_option->getCode() == 'option_ids') {
                                                        $option_ids = explode(',', $_option->getValue());
                                                        foreach($option_ids as $key => $oid) if ($oid == $option['option_id']) unset($option_ids[$key]);
                                                        $_option->setValue(implode(',', $option_ids))->save();
                                                    }
                                                    if ($_option->getCode() == 'option_'.$option['option_id']) $_option->delete();
                                                    if ($_option->getCode() == 'info_buyRequest') {
                                                        $br = json_decode($_option->getValue(), true);
                                                        unset($br['options'][$option['option_id']]);
                                                        unset($br['options_qty'][$option['option_id']]);
                                                        $_option->setValue(json_encode($br))->save();
                                                    }
                                                }
                                            }

                                            unset($productOptions['options'][$optionKey]);

                                            $orderItem->setProductOptions($productOptions);
                                                      
                                            $orderItem->save();
                                            
                                            $needsOrderReload = true;
                                        }

                                        if ($eventName != 'controller_action_predispatch_paypal_express_return') {
                                            $item = $this->_objectManager->create('Magento\CatalogInventory\Model\Stock\Item')->load($valueProduct->getId(), 'product_id');;
                                            if ($item->getManageStock()) {
                                                if ($eventName == 'order_cancel_after') {
                                                    $item->setQty($item->getQty() + $qty * $optionQty);
                                                } else if ($eventName == 'sales_order_creditmemo_refund') {
                                                    $qtyToRefund = intval(@$post['creditmemo']['items'][$orderItem->getId()]['qty']);
                                                    if ($qtyToRefund > 0) $item->setQty($item->getQty() + $qtyToRefund * $optionQty);
                                                } else {
                                                    $item->setQty($item->getQty() - $qty * $optionQty);
                                                }
                                                $item->save();
                                            }
                                        }
                                        if (isset($valueConfiguration['hide_sku']) && $valueConfiguration['hide_sku'] && $valueConfiguration['sku'] && $newOrderItem) {
                                            $orderItem->setSku(str_ireplace(['-'.$newOrderItem->getSku(), '--'], ['', '-'], $orderItem->getSku()));
                                            $orderItem->save();
                                        }
                                    }
                                } else {
                                    if (isset($valueConfiguration['hide_sku']) && $valueConfiguration['hide_sku'] && isset($valueConfiguration['sku']) && $valueConfiguration['sku']) {
                                        $orderItem->setSku(str_ireplace(['-'.$valueConfiguration['sku'], '--'], ['', '-'], $orderItem->getSku()));
                                        $orderItem->save();
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        } //exit;
        
        if ($needsOrderReload) {
            if ($isQuote) {
                $quote = $this->_checkoutSession->getQuote();
                $quote->load($quote->getId());
            } else {
                $order = $observer->getEvent()->getOrder();
                $order->load($order->getId());
            }
        }

        return $this;
    }
}