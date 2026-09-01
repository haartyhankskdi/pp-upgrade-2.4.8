<?php

namespace Nilesh\PrescriberName\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Nilesh\PrescriberName\Model\PrescriberNameFactory;

class PrescriberName extends Column
{
    protected $_orderRepository;
    protected $_searchCriteria;
    protected $_prescriberNameFactory;

    public function __construct(PrescriberNameFactory $prescriberNameFactory, ContextInterface $context, UiComponentFactory $uiComponentFactory, OrderRepositoryInterface $orderRepository, SearchCriteriaBuilder $criteria, array $components = [], array $data = [])
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        $this->_prescriberNameFactory = $prescriberNameFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $prescriber_name = "";
                $order  = $this->_orderRepository->get($item["entity_id"]);
                $rawPrescriberName = $order->getData("prescriber_name");
                $model = $this->_prescriberNameFactory->create();
                $load = $model->load($rawPrescriberName);
                if($rawPrescriberName && $load->getPrescribernameId()){
                    $prescriber_name = $load->getName();
                }
                // $this->getData('name') returns the name of the column so in this case it would return prescriber_name
                $item[$this->getData('name')] = $prescriber_name;
            }
        }

        return $dataSource;
    }
}