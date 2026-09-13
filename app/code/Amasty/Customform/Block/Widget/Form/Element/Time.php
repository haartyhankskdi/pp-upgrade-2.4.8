<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */
/**
 * Copyright В© 2016 Amasty. All rights reserved.
 */
namespace Amasty\Customform\Block\Widget\Form\Element;

class Time extends AbstractElement
{
    public function _construct()
    {
        parent::_construct();
        $this->options['title'] = __('Time');
    }

    public function generateContent()
    {
        return '<input class="form-control" type="time"/>';
    }
}
