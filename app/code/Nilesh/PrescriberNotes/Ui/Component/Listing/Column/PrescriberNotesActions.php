<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Ui\Component\Listing\Column;

class PrescriberNotesActions extends \Magento\Ui\Component\Listing\Columns\Column
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
                if (isset($item['prescribernotes_id'])) {                    
                    $title = $item['subject'];
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    'prescribernotes_id' => $item['prescribernotes_id']
                                ]
                            ),
                            'label' => __('Edit'),
                            'target' => "blank"
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [
                                    'prescribernotes_id' => $item['prescribernotes_id']
                                ]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [                                
                                'title' => __('Delete %1', $title),                                
                                'message' => __('Are you sure you want to delete a %1 record?', $title),
                            ],
                            'post' => true,
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}

