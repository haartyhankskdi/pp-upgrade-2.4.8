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
 * @package   Magezon_UiChooserLayout
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\UiChooserLayout\Block\Adminhtml\Page\Widget;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Chooser extends Extended
{
    /**
     * @var array
     */
    protected $_selectedPages = [];

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context                 $context           
     * @param \Magento\Backend\Helper\Data                            $backendHelper     
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory 
     * @param array                                                   $data              
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Block construction, prepare grid params
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setDefaultSort('name');
        $this->setUseAjax(true);
    }

    /**
     * Prepare chooser element HTML
     *
     * @param AbstractElement $element Form Element
     * @return AbstractElement
     */
    public function prepareElementHtml(AbstractElement $element)
    {
        $uniqId = $this->mathRandom->getUniqueHash($element->getId());
        $sourceUrl = $this->getUrl(
            'uichooserlayout/chooser/pageGrid',
            ['uniq_id' => $uniqId, 'use_massaction' => false]
        );

        $chooser = $this->getLayout()->createBlock(
            \Magento\Widget\Block\Adminhtml\Widget\Chooser::class
        )->setElement(
            $element
        )->setConfig(
            $this->getConfig()
        )->setFieldsetId(
            $this->getFieldsetId()
        )->setSourceUrl(
            $sourceUrl
        )->setUniqId(
            $uniqId
        );

        $element->setData('after_element_html', $chooser->toHtml());
        return $element;
    }

    /**
     * Checkbox Check JS Callback
     *
     * @return string
     */
    public function getCheckboxCheckCallback()
    {
        if ($this->getUseMassaction()) {
            return "function (grid, element) {
                jQuery('#' + grid.containerId).trigger('grid:changed', {element: element});
            }";
        }
    }

    /**
     * Grid Row JS Callback
     *
     * @return string
     */
    public function getRowClickCallback()
    {
        if (!$this->getUseMassaction()) {
            $chooserJsObject = $this->getId();
            return '
                function (grid, event) {
                    var trElement = Event.findElement(event, "tr");
                    var pageId = trElement.down("td").innerHTML;
                    var pageName = trElement.down("td").next().next().innerHTML;
                    var optionLabel = pageName;
                    var optionValue = "page/" + pageId.replace(/^\s+|\s+$/g,"");
                    if (grid.categoryId) {
                        optionValue += "/" + grid.categoryId;
                    }
                    if (grid.categoryName) {
                        optionLabel = grid.categoryName + " / " + optionLabel;
                    }
                    ' .
                $chooserJsObject .
                '.setElementValue(optionValue);
                    ' .
                $chooserJsObject .
                '.setElementLabel(optionLabel);
                    ' .
                $chooserJsObject .
                '.close();
                }
            ';
        }
    }

    /**
     * Category Tree node onClick listener js function
     *
     * @return string
     */
    public function getCategoryClickListenerJs()
    {
        $js = '
            function (node, e) {
                {jsObject}.addVarToUrl("category_id", node.attributes.id);
                {jsObject}.reload({jsObject}.url);
                {jsObject}.categoryId = node.attributes.id != "none" ? node.attributes.id : false;
                {jsObject}.categoryName = node.attributes.id != "none" ? node.text : false;
            }
        ';
        $js = str_replace('{jsObject}', $this->escapeJs($this->getJsObjectName()), $js);
        return $js;
    }

    /**
     * Filter checked/unchecked rows in grid
     *
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_pages') {
            $selected = $this->getSelectedPages();
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('page_id', ['in' => $selected]);
            } else {
                $this->getCollection()->addFieldToFilter('page_id', ['nin' => $selected]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Prepare pages collection, defined collection filters (category, page type)
     *
     * @return Extended
     */
    protected function _prepareCollection()
    {
        /* @var $collection \Magento\Cms\Model\ResourceModel\Page\Collection */
        $collection = $this->_collectionFactory->create();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns for pages grid
     *
     * @return Extended
     */
    protected function _prepareColumns()
    {
        if ($this->getUseMassaction()) {
            $this->addColumn(
                'in_pages',
                [
                    'header_css_class' => 'a-center',
                    'type'             => 'checkbox',
                    'name'             => 'in_pages',
                    'inline_css'       => 'checkbox entities',
                    'field_name'       => 'in_pages',
                    'values'           => $this->getSelectedPages(),
                    'align'            => 'center',
                    'index'            => 'page_id',
                    'use_index'        => true
                ]
            );
        }

        $this->addColumn(
            'chooser_page_id',
            [
                'header'           => __('ID'),
                'sortable'         => true,
                'index'            => 'page_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'chooser_identifier',
            [
                'header'           => __('Identifier'),
                'name'             => 'chooser_identifier',
                'index'            => 'identifier',
                'header_css_class' => 'col-identifier',
                'column_css_class' => 'col-identifier'
            ]
        );
        $this->addColumn(
            'chooser_title',
            [
                'header'           => __('Page'),
                'name'             => 'chooser_title',
                'index'            => 'title',
                'header_css_class' => 'col-page',
                'column_css_class' => 'col-page'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Adds additional parameter to URL for loading only pages grid
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'uichooserlayout/chooser/pageGrid',
            [
                'pages_grid'     => true,
                '_current'       => true,
                'uniq_id'        => $this->getId(),
                'use_massaction' => $this->getUseMassaction()
            ]
        );
    }

    /**
     * Setter
     *
     * @param array $selectedPages
     * @return $this
     */
    public function setSelectedPages($selectedPages)
    {
        $this->_selectedPages = $selectedPages;
        return $this;
    }

    /**
     * Getter
     *
     * @return array
     */
    public function getSelectedPages()
    {
        if ($selectedPages = $this->getRequest()->getParam('selected_pages', null)) {
            $this->setSelectedPages($selectedPages);
        }
        return $this->_selectedPages;
    }
}
