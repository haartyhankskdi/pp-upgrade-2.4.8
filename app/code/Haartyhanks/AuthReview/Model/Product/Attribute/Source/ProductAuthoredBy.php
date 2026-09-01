<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\AuthReview\Model\Product\Attribute\Source;
use Haartyhanks\AuthReview\Model\EntityFactory;

class ProductAuthoredBy extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    protected $model;

    public function __construct(EntityFactory $entityFactory)
    {
        $this->model = $entityFactory;
    }

    /**
     * getAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        
        $this->_options = [];
        $items = $this->getAllItems();
        if (is_array($items)) {
            foreach ($items as $item) {
                $this->_options[] = [
                    'label' => $item['name'],
                    'value' => $item['entity_id']
                ];
            }
        }
       
        return $this->_options;
    }

    public function getAllItems(){
       $collection = $this->model->create()->getCollection();
    // Rakesh Jasediya logger
    $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/product_authored_by.log');
    $logger = new \Zend_Log();
    $logger->addWriter($writer);
    $logger->info('Fetching all items in ProductAuthoredBy');
    $logger->info('Collection size: ' . print_r($collection->getData(), true));
    return $collection->getData();
    }
}

