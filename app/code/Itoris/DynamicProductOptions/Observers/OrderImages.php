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

class OrderImages implements ObserverInterface
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
        $this->_backendConfig = $this->_objectManager->get('Magento\Backend\App\ConfigInterface');
        try {
            $this->isEnabledFlag = $this->_objectManager->get('Itoris\DynamicProductOptions\Helper\Data')->getSettings(true)->getEnabled();
        } catch (\Exception $e) {/** save store model */}
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {

        if (!$this->isEnabledFlag) {
            return null;
        }

        $order = $observer->getEvent()->getOrder();
        
        foreach ($order->getAllItems() as $orderItem) {
            $data = $orderItem->getData();
            if (!isset($data['product_options']['options'])) continue;
            foreach($data['product_options']['options'] as & $option) {
                if ($this->_backendConfig->getValue('itoris_dynamicproductoptions/general/hide_order_images')) {
                    $option['value'] = preg_replace("/<img[^>]+\>/i", "\n", $option['value']); //this to remove images from email
                    $option['value'] = preg_replace("/<div.*<\/div>/Ui", "\n", $option['value']); //this to remove color swatches from email
                }
                $option['print_value'] = preg_replace("/<img[^>]+\>/i", "\n", $option['print_value']); //images
                $option['print_value'] = preg_replace("/<div.*<\/div>/Ui", "\n", $option['print_value']); //color swatches
                $option['print_value'] = str_replace("<br/>", ", ", $option['print_value']);
            }
            $orderItem->setData($data);
        }
        
        return $this;
    }
}