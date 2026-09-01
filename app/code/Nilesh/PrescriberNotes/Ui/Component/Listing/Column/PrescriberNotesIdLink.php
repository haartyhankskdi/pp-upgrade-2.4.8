<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Ui\Component\Listing\Column;

class PrescriberNotesIdLink extends \Magento\Ui\Component\Listing\Columns\Column
{

    const URL_PATH_DETAILS = 'nilesh_prescribernotes/prescribernotes/details';
    const URL_PATH_DELETE = 'nilesh_prescribernotes/prescribernotes/delete';
    protected $urlBuilder;
    const URL_PATH_EDIT = 'nilesh_prescribernotes/prescribernotes/edit';

    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['note'])) {
//                    $item[$this->getData('name')] = [
//                        'view' => [
//                            'href' => $this->urlBuilder->getUrl(
//                                static::URL_PATH_EDIT,
//                                [
//                                    'id' => $item['connect_id']
//                                ]
//                            ),
//                            'label' => $item['connect_id'],
//                            'target' => "blank"
//                        ]
//                    ];
                    $item[$this->getData('name')] = html_entity_decode("<div class='note_text' style='display: none'>" . htmlspecialchars($item['note']) . "</div><button data-createdat='".$item['created_at'] ."' class='nd-prescriber-note-modal-button'>View</button>");
                }
            }
        }


        return $dataSource;
    }
}

