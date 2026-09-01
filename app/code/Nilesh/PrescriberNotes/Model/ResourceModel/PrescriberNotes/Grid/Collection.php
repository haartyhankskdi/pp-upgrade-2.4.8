<?php

namespace Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes\Grid;

use Magento\Framework\Api;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Customer\Controller\RegistryConstants;

//use Magento\Framework\Api\Search\SearchResultInterface;



class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        \Magento\Framework\Registry $registry

    )
    {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            "nilesh_prescribernotes_prescribernotes",
            \Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes\Collection::class,
            null,
            null
        );
        $this->_coreRegistry = $registry;
    }

    /**
     * Redeclare before load method for adding event
     *
     * @return $this
     */
    protected function _beforeLoad()
    {
        $paramValue = $this->getCustomerId();
        if($paramValue){
            $this->addFieldTOFilter( 'connect_id', array( 'eq' =>  $paramValue) );
        }
        return $this;
    }


    private function getCustomerId()
    {
        $_customerId = @$_COOKIE["nd_current_customer_id"];
        $customerId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
//        if(!empty($customerId) && $customerId != $_customerId){
        if(!empty($customerId)){
            setcookie("nd_current_customer_id", $customerId, time() + (86400 * 30), "/");
        }
        return !empty($customerId) ? $customerId : $_customerId;
    }
}
