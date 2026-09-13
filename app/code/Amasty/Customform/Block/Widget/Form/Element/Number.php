<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Block\Widget\Form\Element;

class Number extends Textinput
{
    public function _construct()
    {
        parent::_construct();
        $this->options['title'] = __('Number Input');
        $this->options['image_href'] = 'Amasty_Customform::images/textarea.png';
    }

    public function generateContent()
    {
        return '<input class="form-control" type="number"/>';
    }
}
