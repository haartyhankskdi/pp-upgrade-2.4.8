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

namespace Magezon\PopupBuilder\Block\Element;

class PopupAction extends \Magezon\Builder\Block\Element\Button
{
    /**
     * @return array
     */
    public function getWrapperAttributes()
    {
        $attrs = parent::getWrapperAttributes();
        $actionType = $this->getElement()->getData('action_type');
        if ($actionType != 'link') {
            $attrs['data-action-type'] = $actionType;
        }
        return $attrs;
    }
}
