<?php

namespace Nilesh\PrescriberNotes\Ui\Component\Listing\Column;

class Attachment extends \Magento\Ui\Component\Listing\Columns\Column {

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ){
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_storeManager = $storeManager;
    }

    public function prepareDataSource(array $dataSource) {
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'nilesh_prescribernotes/';
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if(!empty($item['prescribernotes_upload']) || !empty($item['prescribernotes_upload2']) || !empty($item['prescribernotes_upload3']) || !empty($item['prescribernotes_upload4']) || !empty($item['prescribernotes_upload5'])){
                    //$item['attachment'] = "Yes";
                    $item[$this->getData('name')] = 
                        "<div class='note_text' style='display: none'>".htmlentities("<a href='".$mediaUrl.$item['prescribernotes_upload']."' download>" . htmlspecialchars($item['prescribernotes_upload']) .  "</a></br>")."</div>
                         <div class='note_text' style='display: none'>".htmlentities("<a href='".$mediaUrl.$item['prescribernotes_upload2']."' download>" . htmlspecialchars($item['prescribernotes_upload2']) . "</a></br>")."</div>
                         <div class='note_text' style='display: none'>".htmlentities("<a href='".$mediaUrl.$item['prescribernotes_upload3']."' download>" . htmlspecialchars($item['prescribernotes_upload3']) . "</a></br>")."</div>
                         <div class='note_text' style='display: none'>".htmlentities("<a href='".$mediaUrl.$item['prescribernotes_upload4']."' download>" . htmlspecialchars($item['prescribernotes_upload4']) . "</a></br>")."</div>
                         <div class='note_text' style='display: none'>".htmlentities("<a href='".$mediaUrl.$item['prescribernotes_upload5']."' download>" . htmlspecialchars($item['prescribernotes_upload5']) . "</a>")."</div>
                        <button class='nd-prescriber-attachment-modal-button'>View/Download</button>";
                }else{
                    $item['attachment'] = "No";
                }
            }
        }
        return $dataSource;
    }
}
?>


