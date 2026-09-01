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

class Pages extends \Magento\Widget\Controller\Adminhtml\Widget\Instance
{
    /**
     * Pages chooser Action (Ajax request)
     *
     * @return void
     */
    public function execute()
    {
        $result   = [];
        $id       = $this->getRequest()->getParam('id');
        $selected = $this->getRequest()->getParam('selected', '');
        $chooser  = $this->_view->getLayout()->createBlock(
            \Magezon\UiChooserLayout\Block\Adminhtml\Page\Widget\Chooser::class
        )->setId(
            'grid' . $id
        )->setName(
            $this->mathRandom->getUniqueHash('pages_grid_')
        )->setUseMassaction(
            true
        )->setSelectedPages(
            explode(',', $selected)
        );
        /* @var $serializer \Magento\Backend\Block\Widget\Grid\Serializer */
        $serializer = $this->_view->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Grid\Serializer::class,
            '',
            [
                'data' => [
                    'grid_block'         => $chooser,
                    'callback'           => 'getSelectedPages',
                    'input_element_name' => 'selected_pages',
                    'reload_param_name'  => 'selected_pages'
                ]
            ]
        );
        $html = $chooser->toHtml() . $serializer->toHtml();
        $result['html'] = $html;
        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
        return;
    }
}
