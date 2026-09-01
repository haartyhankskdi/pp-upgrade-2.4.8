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

class Report extends Generic
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->_isAllowedAction('Magezon_PopupBuilder::report')) {
            if ($popup = $this->getCurrentPopup()) {
                if ($popupId = $popup->getId()) {
                    $reportUrl = $this->getUrl('popupbuilder/reports', ['popup_id' => $popupId]);
                    $data = [
                        'label'      => __('Report'),
                        'class'      => 'report',
                        'on_click'   => 'window.open(\'' . $reportUrl . '\', \'_self\')',
                        'sort_order' => 30
                    ];
                }
            }
        }
        return $data;
    }
}
