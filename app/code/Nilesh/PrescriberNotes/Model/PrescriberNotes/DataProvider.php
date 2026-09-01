<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Model\PrescriberNotes;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes\CollectionFactory;
use Nilesh\PrescriberNotes\Model\PrescriberNotes;


class DataProvider extends AbstractDataProvider
{

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;
    /**
     * @inheritDoc
     */
    protected $collection;
    /**
     * @inheritDoc
     */
    protected $prescribernotes;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectmanager;


    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param PrescriberNotes $prescribernotes
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        PrescriberNotes $prescribernotes,
        DataPersistorInterface $dataPersistor, 
        \Magento\Framework\ObjectManagerInterface $objectmanager,       
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->prescribernotes = $prescribernotes;
        $this->dataPersistor = $dataPersistor;        
        $this->objectmanager = $objectmanager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
    }


    /**
     * Prepares Meta
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta)
    {
        return $meta;
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        //echo "<pre>";print_r($this->prescribernotes->getPrescribernotesUpload());exit();
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();   
        $prescribernotes = $this->objectmanager->create(\Nilesh\PrescriberNotes\Model\PrescriberNotes::class);     
        $storeManager = $this->objectmanager->get('Magento\Store\Model\StoreManagerInterface'); 
        $currentStore = $storeManager->getStore();
        $mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        foreach ($items as $model) {            

            $presData = $prescribernotes->load($model->getId()); //temporary fix
            $data = $presData->getData();

            /* Prepare Prescriber Image */
            $map = [
                'prescribernotes_upload'  => 'getPrescribernotesUpload',
                'prescribernotes_upload2' => 'getPrescribernotesUpload2',
                'prescribernotes_upload3' => 'getPrescribernotesUpload3',
                'prescribernotes_upload4' => 'getPrescribernotesUpload4',            
                'prescribernotes_upload5' => 'getPrescribernotesUpload5'
            ];
            foreach ($map as $key => $method) {
                if (isset($data[$key])) {
                    $name = $data[$key];
                    unset($data[$key]);
                    $data[$key][0] = [
                        'name' => $name,
                        'url' => $mediaUrl.'nilesh_prescribernotes/'.$name,
                    ];
                }
            }

            $data['data'] = ['links' => []];

            /* Set data */
            $this->loadedData[$model->getId()] = $data;
            //$this->loadedData[$model->getId()] = $model->getData(); 
        }

        $data = $this->dataPersistor->get('nilesh_prescribernotes_prescribernotes');

        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($data);
            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('nilesh_prescribernotes_prescribernotes');
        }

        return $this->loadedData;        
    }
}

