<?php
/**
 * Copyright © No All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Consultation\Plugin\Amasty\Customform\Controller\Form;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Kdi\Consultation\Helper\CustomSession;

class Submit
{

    protected $request;
    protected $product;
    protected $registry;
    protected $customSession;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        Product $product,
        Registry $registry,
        CustomSession $customSession
    ){
        $this->request = $request;
        $this->product = $product;
        $this->registry = $registry;
        $this->customSession = $customSession;
    }

    public function beforeExecute(
        \Amasty\Customform\Controller\Form\Submit $subject
    ): array {
        
        
            $request = $subject->getRequest();


            if (!empty($this->customSession->get())) {
                 $postData['hh_product_id'] = 'value_added_by_plugin';
            }
        // Modify POST data
        $postData = $request->getPostValue();
        $postData['hh_product_id'] = 'value_added_by_plugin';
        

        echo "<pre>";
        echo "product Name " . $this->getProduct()->getName();
        print_r($this->request->getParams());

        echo "aaaaaaaaaaaaa;";
        print_r($postData);

        return [];
    }


     private function getProduct()
    {

        $product =  $this->registry->registry('product');
        //     print_r($product->getData());



        // // if (is_null($this->product)) {
            

        // //     if (!$this->product->getId()) {
        // //         throw new LocalizedException(__('Failed to initialize product'));
        // //     }
        // // }

        return $this->product;
    }

    public function getProductName()
    {
        return $this->getProduct()->getName();
    }
}

