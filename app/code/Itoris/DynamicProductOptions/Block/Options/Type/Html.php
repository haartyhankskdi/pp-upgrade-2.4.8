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

//app/code/Itoris/DynamicProductOptions/Block/Options/Type/Html.php
namespace Itoris\DynamicProductOptions\Block\Options\Type;

class Html extends AbstractOptions
{
    protected function _construct() {
        $this->setTemplate('Itoris_DynamicProductOptions::option/html.phtml');
    }
    
    public function parseMediaVariables($str) {
        $str = str_replace(['{{media url=&quot;','{{store direct_url=&quot;', '&quot;}}'],['{{media url="', '{{store direct_url="', '"}}'], $str);
        
        preg_match_all('/{{media url=\"(.*?)\"}}/', $str, $matches);
        $mediaUrl = $this->getMediaUrl();
        foreach($matches[0] as $key => $match) $str = str_replace($matches[0][$key], $mediaUrl.$matches[1][$key], $str);
        
        preg_match_all('/{{store direct_url=\"(.*?)\"}}/', $str, $matches);
        $baseUrl = $this->getBaseUrl();
        foreach($matches[0] as $key => $match) $str = str_replace($matches[0][$key], $baseUrl.$matches[1][$key], $str);
        return $str;
    }
    
    public function getMediaUrl(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore(0);
        return $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA );
    }
    
    public function getBaseUrl(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
        return $store->getBaseUrl();
    }
}