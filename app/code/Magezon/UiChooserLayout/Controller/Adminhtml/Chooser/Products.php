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

namespace Magezon\UiChooserLayout\Controller\Adminhtml\Chooser;

class Products extends \Magento\Widget\Controller\Adminhtml\Widget\Instance
{
    /**
     * Products chooser Action (Ajax request)
     *
     * @return void
     */
    public function execute()
    {
        $result = [];
        $id           = $this->getRequest()->getParam('id');
        $selected = $this->getRequest()->getParam('selected', '');
        $productTypeId = $this->getRequest()->getParam('product_type_id', '');
        $chooser = $this->_view->getLayout()->createBlock(
            \Magento\Catalog\Block\Adminhtml\Product\Widget\Chooser::class
        )->setId(
            'grid' . $id
        )->setName(
            $this->mathRandom->getUniqueHash('products_grid_')
        )->setUseMassaction(
            true
        )->setProductTypeId(
            $productTypeId
        )->setSelectedProducts(
            explode(',', $selected)
        );
        /* @var $serializer \Magento\Backend\Block\Widget\Grid\Serializer */
        $serializer = $this->_view->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Grid\Serializer::class,
            '',
            [
                'data' => [
                    'grid_block'         => $chooser,
                    'callback'           => 'getSelectedProducts',
                    'input_element_name' => 'selected_products',
                    'reload_param_name'  => 'selected_products'
                ]
            ]
        );
        $html = str_replace("$(grid.containerId).fire('product:changed', {element: element});", "jQuery('#' + grid.containerId).trigger('grid:changed', {element: element});", $chooser->toHtml() . $serializer->toHtml());
        $result['html'] = $html;
        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
        return;
    }
}
