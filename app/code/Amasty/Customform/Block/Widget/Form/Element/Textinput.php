<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Block\Widget\Form\Element;

class Textinput extends AbstractElement
{
    public function _construct()
    {
        parent::_construct();
        $this->options['title'] = __('Text Input');
    }

    public function generateContent()
    {
        return '<input class="form-control" type="text"/>';
    }
}
