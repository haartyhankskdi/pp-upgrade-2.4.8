<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Mageplaza\PromoBanner\Helper\Data;

/**
 * Class Category
 *
 * @package Mageplaza\PromoBanner\Ui\Component\Listing\Column
 */
class Category extends Column
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * Category constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Data $helperData
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Data $helperData,
        array $components = [],
        array $data = []
    ) {
        $this->helperData = $helperData;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName    = $this->getName();
            $categoryList = $this->helperData->getCategoryList();
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($categoryList[$item[$fieldName]])) {
                    $item[$fieldName] = $categoryList[$item[$fieldName]]['label'];
                } else {
                    $item[$fieldName] = null;
                }
            }
        }

        return $dataSource;
    }
}
