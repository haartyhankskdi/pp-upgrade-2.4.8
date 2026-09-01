<?php declare(strict_types=1);
/**
 * This module is created by Nilesh Dubey
 * Copyright (C) 2020  Free
 * 
 * This file is part of Nilesh/ProductCustomOption.
 * 
 * Nilesh/ProductCustomOption is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Nilesh\ProductCustomOption\Controller\Prefilled;


class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
    protected $_customerSession;
    protected $_salesCollection;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customer_session,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesCollection
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->_customerSession = $customer_session;
        $this->_salesCollection = $salesCollection;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {   
        // Contional var
        $stopAtOne = 0;
        $reponseArray = array();

        // testing weather customer is login or not
        if(!$this->_customerSession->isLoggedIn()){
            $this->jsonResponse($reponseArray);
        }
        
        try {
            
        $post = $this->getRequest()->getPostValue();
        $post_product_id = $post['product_id'];
        // echo json_encode(array('product_id' => $post_product_id));
        // exit();

        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $customerSession = $this->_customerSession->create();
        $_orderCollectionFactory = $this->_salesCollection->create();
        $collection = $_orderCollectionFactory->addAttributeToSelect('*')->addFieldToFilter('customer_id',$this->_customerSession->getCustomerId())->setOrder('created_at','desc');
        // echo $this->_customerSession->getCustomerId();
        foreach ($collection as $_order){
            // \print_r($_order->getData()); - get order info
            foreach ($_order->getAllVisibleItems() as $_item) {   
                $product_id = $_item->getProductId();
                if($product_id == $post_product_id && $stopAtOne == 0){
                    $stopAtOne = 1;
                    $options = $_item->getProductOptions();        
                    if (isset($options['options']) && !empty($options['options'])) {        
                        foreach ($options['options'] as $option) {
                            // echo 'Title: ' . $option['label'] . '<br />';
                            // echo 'ID: ' . $option['option_id'] . '<br />';
                            // echo 'Type: ' . $option['option_type'] . '<br />';
                            // echo 'Value: ' . $option['option_value'] . '<br />' . '<br />';
                            $reponseArray[] = array(
                                "order_id" => $option['option_id'],
                                "order_field" => $option['option_type'],
                                "order_value" => $option['option_value']
                            );
                        }
                    } 
                    break;
                }else{                 
                      
                }                 
            }
        }
            // echo "<pre>";
            // \print_r($reponseArray); 
            $this->jsonResponse($reponseArray);
            //  exit();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}

