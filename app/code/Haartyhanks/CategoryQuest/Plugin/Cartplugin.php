<?php
namespace Haartyhanks\CategoryQuest\Plugin;

class Cartplugin 
{
     protected $_url;
     protected $request;
     protected $helperdata;
     protected $storeManager;
	
     public function __construct(\Magento\Framework\UrlInterface $url,
	    \Magento\Framework\App\Request\Http $request,
	    \Magento\Store\Model\StoreManagerInterface $storeManager)
     {
          $this->_url = $url;
          $this->request = $request;
	         $this->storeManager = $storeManager;
     }
		
     public function beforeAddProduct($subject, $productInfo, $requestInfo = null)
     {
          $cartrtnurl=$this->storeManager->getStore()->getBaseUrl()."checkout/";
          if($cartrtnurl != '' && isset($cartrtnurl))
	         {
	              $accUrl = $this->_url->getUrl($cartrtnurl);
	              $this->request->setParam('return_url', $accUrl);
	         }
          return [$productInfo, $requestInfo];
     }
}