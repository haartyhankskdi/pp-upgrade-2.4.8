<?php

namespace Nilesh\PrescriberNotes\Ui\Component\Listing\Column;

class SetPriority extends \Magento\Ui\Component\Listing\Columns\Column {

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
                if($item['set_priority'] == 'High'){
                    $item['set_priority'] = html_entity_decode("<span class='high red-color'>".$item['set_priority'].'</span>');
                }
                if($item['set_priority'] == 'Medium'){
                    $item['set_priority'] = html_entity_decode("<span class='medium yellow-color'>".$item['set_priority'].'</span>');
                }
                if($item['set_priority'] == 'Low'){
                    $item['set_priority'] = html_entity_decode("<span class='low blue-color'>".$item['set_priority'].'</span>');
                }
            }
        }
        return $dataSource;
    }
}
?>


