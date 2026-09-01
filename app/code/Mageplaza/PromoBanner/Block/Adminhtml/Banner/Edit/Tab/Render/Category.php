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

namespace Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Ui\Component\Product\Form\Categories\Options;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Mageplaza\PromoBanner\Helper\Data as PromoBannerData;

/**
 * Class Category
 *
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render
 */
class Category extends AbstractElement
{

    /**
     * @var CategoryCollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var Options
     */
    protected $categoryOptions;

    /**
     * Category constructor.
     *
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param CategoryCollectionFactory $collectionFactory
     * @param Options $categoryOptions
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        CategoryCollectionFactory $collectionFactory,
        Options $categoryOptions,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->categoryOptions    = $categoryOptions;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getElementHtml()
    {
        $html = '<div class="admin__field-control admin__control-grouped" id="' . $this->getHtmlId() . '">';
        $html .= '<div id="mppromobanner_select_category" class="admin__field" 
data-bind="scope:\'mppromobanner_catalog_category\'" data-index="index">';
        $html .= '<!-- ko foreach: elems() -->';
        $html .= '<input type="hidden" name="mppromobanner[category_ids]" data-bind="value: value"/>';
        $html .= '<!-- ko template: elementTmpl --><!-- /ko -->';
        $html .= '<!-- /ko -->';
        $html .= '</div></div>';

        $html .= $this->getScriptHtml();

        return $html;
    }

    /**
     * Customize Categories field
     *
     * @return string
     * @throws LocalizedException
     */
    public function getScriptHtml()
    {
        $html = '<script type="text/x-magento-init">
            {
                "*": {
                    "Magento_Ui/js/core/app": {
                        "components": {
                            "mppromobanner_catalog_category": {
                                "component": "uiComponent",
                                "children": {
                                    "mppromobanner_category_ids": {
                                        "component": "Magento_Catalog/js/components/new-category",
                                        "config": {
                                            "formElement": true,
                                            "componentType": true,
                                            "filterOptions": true,
                                            "disableLabel": true,
                                            "chipsEnabled": true,
                                            "levelsVisibility": "1",
                                            "elementTmpl": "ui/grid/filters/elements/ui-select",
                                            "options": ' . PromoBannerData::jsonEncode($this->categoryOptions->toOptionArray()) . ',
                                            "value": ' . PromoBannerData::jsonEncode($this->getValues()) . ',
                                            "config": {
                                                "dataScope": "mppromobanner_category_ids",
                                                "sortOrder": 10
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        </script>';

        return $html;
    }

    /**
     * Get values for select
     *
     * @return array
     * @throws LocalizedException
     */
    public function getValues()
    {
        $collection = $this->_getCategoriesCollection();
        $values     = $this->getValue();
        if (!is_array($values)) {
            $values = explode(',', $values);
        }
        $collection->addAttributeToSelect('name');
        $collection->addIdFilter($values);

        $options = [];

        foreach ($collection as $category) {
            $options[] = $category->getId();
        }

        return $options;
    }

    /**
     * Get categories collection
     *
     * @return Collection
     */
    protected function _getCategoriesCollection()
    {
        return $this->_collectionFactory->create();
    }
}
