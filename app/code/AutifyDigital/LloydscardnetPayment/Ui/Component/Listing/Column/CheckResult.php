<?php

/**
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-2025 Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */

declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use AutifyDigital\LloydscardnetPayment\Helper\Data as Helper;

class CheckResult extends Column
{
    /**
     * @var Helper
     */
    private $helper;
    
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Helper $helper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Helper $helper,
        array $components = [],
        array $data = []
    ) {
        $this->helper = $helper;
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
            $fieldName = $this->getData('name');
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName . '_original'] = $item[$fieldName];
                    $item[$fieldName] = $this->helper->getCheckResult($item[$fieldName]);
                } else {
                    $item[$fieldName . '_original'] = '';
                    $item[$fieldName] = '<span class="check-unchecked">' . __('Unchecked') . '</span>';
                }
            }
        }
        return $dataSource;
    }
}
