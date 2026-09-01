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

namespace Magezon\PopupBuilder\Block\Adminhtml\Popup\Edit\Button;

class Delete extends Generic
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getCurrentPopup()->getId() && $this->_isAllowedAction('Magezon_PopupBuilder::popup_delete')) {
            $data = [
                'label'    => __('Delete'),
                'class'    => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this?'
                ) . '\', \'' . $this->getUrl('*/*/delete', ['popup_id' => $this->getCurrentPopup()->getId()]) . '\')',
                'sort_order' => 20
            ];
        }
        return $data;
    }
}
