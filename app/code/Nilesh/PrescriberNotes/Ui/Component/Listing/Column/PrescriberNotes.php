<?php

namespace Nilesh\PrescriberNotes\Ui\Component\Listing\Column;

class PrescriberNotes extends \Magento\Ui\Component\Listing\Columns\Column {

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ){
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource) {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if(strlen($item['note']) > 15){
                    $item['note'] = substr($item['note'],0,15).' ...';
                }else{
                    $item['note'] = $item['note'];
                }
            }
        }
        return $dataSource;
    }
}