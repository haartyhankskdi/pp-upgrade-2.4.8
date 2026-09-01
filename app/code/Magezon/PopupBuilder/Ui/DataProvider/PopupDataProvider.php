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

namespace Magezon\PopupBuilder\Ui\DataProvider;

use Magento\Framework\App\ObjectManager;
use Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory;

class PopupDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magezon\PopupBuilder\Model\ResourceModel\Popup\Collection
     */
    protected $collection;

    /**
     * @var \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]
     */
    protected $addFieldStrategies;

    /**
     * @var \Magento\Ui\DataProvider\AddFilterToCollectionInterface[]
     */
    protected $addFilterStrategies;

    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Ui\DataProvider\AddFieldToCollectionInterface[] $addFieldStrategies
     * @param \Magento\Ui\DataProvider\AddFilterToCollectionInterface[] $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->collection->getSelect()
        ->joinLeft(
            ['mppr' => $this->collection->getTable('mgz_popupbuilder_popup_report')],
            "main_table.popup_id = mppr.popup_id",
            [   
                'views'  => "COUNT(CASE WHEN mppr.type = 'open' THEN 1 END)",
                'clicks' => "COUNT(CASE WHEN mppr.type = 'click' THEN 1 END)",
                'closes' => "COUNT(CASE WHEN mppr.type = 'close' THEN 1 END)",
                'ctr'    => "round((COUNT(CASE WHEN mppr.type = 'click' THEN 1 END) / COUNT(CASE WHEN mppr.type = 'open' THEN 1 END) * 100), 2)"
            ]
        )->group('main_table.popup_id');
        $this->addFieldStrategies  = $addFieldStrategies;
        $this->addFilterStrategies = $addFilterStrategies;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        $items = $this->getCollection()->toArray();

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items'        => $items['items']
        ];
    }

    /**
     * Add field to select
     *
     * @param string|array $field
     * @param string|null $alias
     * @return void
     */
    public function addField($field, $alias = null)
    {
        if (isset($this->addFieldStrategies[$field])) {
            $this->addFieldStrategies[$field]->addField($this->getCollection(), $field, $alias);
        } else {
            parent::addField($field, $alias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == 'fulltext') {
            $fulltextFilter = ObjectManager::getInstance()->get(
                \Magezon\PopupBuilder\Ui\Component\Popup\FulltextFilter::class
            );
            $fulltextFilter->addFilter(
                $this->getCollection(),
                $filter->getField(),
                [$filter->getConditionType() => $filter->getValue()]
            );
        } else {
            if (isset($this->addFilterStrategies[$filter->getField()])) {
                $this->addFilterStrategies[$filter->getField()]
                    ->addFilter(
                        $this->getCollection(),
                        $filter->getField(),
                        [$filter->getConditionType() => $filter->getValue()]
                    );
            } else {
                parent::addFilter($filter);
            }
        }
    }
}
