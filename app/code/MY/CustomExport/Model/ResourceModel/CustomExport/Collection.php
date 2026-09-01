<?php 

namespace MY\CustomExport\Model\ResourceModel\CustomExport;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
        /**
         * Define resource model
         *
         * @return void
         */
        public function __construct(
	        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
	        \Psr\Log\LoggerInterface $logger,
	        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
	        \Magento\Framework\Event\ManagerInterface $eventManager,
	        \Magento\Store\Model\StoreManagerInterface $storeManager,
	        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
	        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
	    ) {
	        $this->_init(
	            'MY\CustomExport\Model\CustomExport',
	            'MY\CustomExport\Model\ResourceModel\CustomExport'
	        );
	        parent::__construct(
	            $entityFactory, $logger, $fetchStrategy, $eventManager, $connection,
	            $resource
	        );
	    }

	    protected function _initSelect()
		{
		    $this->addFilterToMap('entity_id', 'main_table.entity_id');
		    $this->addFilterToMap('created_at', 'main_table.created_at');
		    parent::_initSelect();

		    return $this->getSelect(

		    // )->joinLeft(
		    //     ['sixTable' => $this->getTable('nilesh_generalquestions_generalquestions')],
		    //     'main_table.customer_id = sixTable.customer_id',
		    //     ['suffer_diagnosed_yes','other_medication_yes','have_allergies_yes','registered_gp','registered_gp_surgery','upload_documents_prescriber_yes'] 
		    )
			->group('main_table.entity_id')
			->joinLeft(
		        ['thirdTable' => $this->getTable('sales_order_item')],
		        'main_table.entity_id = thirdTable.order_id AND thirdTable.product_type = \'simple\'',
		        ['sku','name','product_options']
		    )
			->joinLeft(
				['customer' => $this->getTable('customer_entity')],
				'main_table.customer_id = customer.entity_id',
				[
					'unique_id' => 'customer.entity_id'
				],
			)->joinLeft(
		        ['secondTable' => $this->getTable('sales_order_address')],
		        'main_table.entity_id = secondTable.parent_id AND secondTable.address_type = \'billing\'',
		        ['customer_dob' => "date(customer_dob)",'country_id','bill_name' => "CONCAT(secondTable.firstname, ' ', secondTable.lastname)",'billing_address' => "CONCAT(secondTable.street, ' ', secondTable.city, ' ',secondTable.region, ' ',secondTable.postcode)"]  
		    )->joinLeft(
                    ['shippingAddress' => $this->getTable('sales_order_address')],
                     'main_table.entity_id = shippingAddress.parent_id AND shippingAddress.address_type = \'shipping\'',
                       [
                     'ship_name' => "CONCAT(shippingAddress.firstname, ' ', shippingAddress.lastname)",
                     'shipping_address' => "CONCAT(shippingAddress.street, ' ', shippingAddress.city, ' ', shippingAddress.region, ' ', shippingAddress.postcode)"
                   ]	    
		    )->joinLeft(
		        ['fivthTable' => $this->getTable('sales_shipment_track')],
		        'main_table.entity_id = fivthTable.order_id',
		        ['track_number']   		
		    )->order('main_table.created_at DESC');  

		    // echo $this->getSelect()->__toString();
		    //  exit();

		}
}

?>