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

use Magento\Ui\Component\Control\Container;

class Save extends Generic
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->_isAllowedAction('Magezon_PopupBuilder::popup_save')) {
            return [];
        }

        return [
            'label'          => __('Save'),
            'class'          => 'save primary',
            'class_name'     => Container::SPLIT_BUTTON,
            'options'        => $this->getOptions(),
            'data_attribute' => $this->getButtonAttribute()
        ];
    }

    /**
     * Retrieve options
     *
     * @return array
     */
    protected function getOptions()
    {
        $options[] = [
            'id_hard'        => 'save_and_new',
            'label'          => __('Save & New'),
            'data_attribute' => $this->getButtonAttribute([ true, ['back' => 'save_and_new']])
        ];

        $options[] = [
            'label'          => __('Save & Duplicate'),
            'id_hard'        => 'save_and_duplicate',
            'data_attribute' => $this->getButtonAttribute([ true, ['back' => 'save_and_duplicate']])
        ];

        $options[] = [
            'id_hard'        => 'save_and_close',
            'label'          => __('Save & Close'),
            'data_attribute' => $this->getButtonAttribute([ true, ['back' => 'save_and_close']])
        ];

        return $options;
    }
}
