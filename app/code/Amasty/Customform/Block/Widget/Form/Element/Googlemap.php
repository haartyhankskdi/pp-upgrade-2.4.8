<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Block\Widget\Form\Element;

class Googlemap extends AbstractElement
{
    public function _construct()
    {
        parent::_construct();
        $this->options['title'] = __('Google Map');
    }

    public function generateContent()
    {
        return '<div></div>';
    }
}
