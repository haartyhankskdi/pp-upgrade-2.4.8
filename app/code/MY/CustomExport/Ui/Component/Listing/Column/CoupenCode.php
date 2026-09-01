<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MY\CustomExport\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;


/**
 * Class Status
 */
class CoupenCode extends Column
{
    /**
     * @var string[]
     */
    protected $collection;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $collectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,      
        array $components = [],
        array $data = []
    ) {        
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
            	if (isset($item['coupon_code'])) {
                    $item[$this->getData('name')] = $item['coupon_code'];
                } else {
                    $item[$this->getData('name')] = __(' '); 
                }       	
            }
        }

        return $dataSource;
    }
}
