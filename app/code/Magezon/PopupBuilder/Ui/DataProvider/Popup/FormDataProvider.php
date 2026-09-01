<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Ui\DataProvider\Popup;

use Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

class FormDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magezon\PopupBuilder\Model\ResourceModel\Popup\Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magezon\Core\Helper\Data
     */
    protected $coreHelper;

    /**
     * @var \Magezon\PopupBuilder\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * @var array
     */
    protected $meta;

    /**
     * @param string                            $name
     * @param string                            $primaryFieldName
     * @param string                            $requestFieldName
     * @param \Magento\Framework\Registry       $registry
     * @param CollectionFactory                 $popupCollectionFactory
     * @param \Magezon\Core\Helper\Data         $coreHelper
     * @param \Magezon\PopupBuilder\Helper\Data $dataHelper
     * @param DataPersistorInterface            $dataPersistor
     * @param PoolInterface                     $pool
     * @param array                             $meta
     * @param array                             $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Framework\Registry $registry,
        CollectionFactory $popupCollectionFactory,
        \Magezon\Core\Helper\Data $coreHelper,
        \Magezon\PopupBuilder\Helper\Data $dataHelper,
        DataPersistorInterface $dataPersistor,
        PoolInterface $pool,
        array $meta = [],
        array $data = []
    ) {
        $this->collection    = $popupCollectionFactory->create();
        $this->registry      = $registry;
        $this->coreHelper    = $coreHelper;
        $this->dataHelper    = $dataHelper;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
        $this->pool = $pool;
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
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $popup = $this->getCurrentPopup();
        if ($popup && $popup->getId()) {
            $popupData = $popup->getData();

            /** @var \Magento\Ui\DataProvider\Modifier\ModifierInterface $modifier */
            foreach ($this->pool->getModifiersInstances() as $modifier) {
                $popupData = $modifier->modifyData($popupData);
            }
            $this->loadedData[$popup->getId()] = $popupData;
        }

        $data = $this->dataPersistor->get('current_popup');
        if (!empty($data)) {
            $popup = $this->collection->getNewEmptyItem();
            $popup->setData($data);
            $this->loadedData[$popup->getId()] = $popup->getData();
            $this->dataPersistor->clear('current_popup');
        }

        if ($this->loadedData) {
            $first = reset($this->loadedData);
            if (isset($first['conditions'])) {
                $first['conditions'] = $this->dataHelper->dataPreprocessing(
                    $this->coreHelper->unserialize($first['conditions'])
                );
            }
            if (isset($first['display_settings'])) {
                $first['display_settings'] = $this->dataHelper->dataPreprocessing(
                    $this->coreHelper->unserialize($first['display_settings'])
                );
            }
            if (isset($first['style_settings'])) {
                $first['style_settings'] = $this->dataHelper->dataPreprocessing(
                    $this->coreHelper->unserialize($first['style_settings'])
                );
            }

            if (isset($first['salerule_id'])) {
                $first['conditions']['salerule_id'] = $first['salerule_id'];
            }

            $key = key($this->loadedData);
            $this->loadedData[$key] = $first;
        }

        return $this->loadedData;
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        /** @var \Magento\Ui\DataProvider\Modifier\ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        $meta = $this->prepareMeta($meta);

        return $meta;
    }

    /**
     * Get current popup
     *
     * @return \Magezon\PopupBuilder\Model\Popup
     */
    public function getCurrentPopup()
    {
        return $this->registry->registry('current_popup');
    }
}
