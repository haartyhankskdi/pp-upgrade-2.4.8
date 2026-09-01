<?php

namespace Nilesh\PrescriberNotes\Ui\Component\Form\PrescriberNotes;

use Magento\Store\Model\StoreManagerInterface;
use Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes\Collection;
use Magento\Framework\App\RequestInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;


    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Collection $collection,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collection;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->loadedData) {
            $storeId = (int) $this->request->getParam('store');
            $this->collection->setStoreId($storeId)->addAttributeToSelect('*');
            $items = $this->collection->getItems();
            foreach ($items as $item) {
                $item->setStoreId($storeId);
                $itemData = $item->getData();
                if (isset($itemData['logo'])) {
                    $imageName = explode('/', $itemData['logo']);
                    $itemData['logo'] = [
                        [
                            'name' => $imageName[3],
                            'url' => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'logo/image' . $itemData['logo'],
                        ],
                    ];
                } else {
                    $itemData['logo'] = null;
                }
                
                $this->loadedData[$item->getEntityId()] = $itemData;
                break;
            }
        }
        return $this->loadedData;
    }
}