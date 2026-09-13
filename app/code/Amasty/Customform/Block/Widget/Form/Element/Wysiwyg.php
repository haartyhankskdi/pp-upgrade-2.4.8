<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Block\Widget\Form\Element;

class Wysiwyg extends AbstractElement
{
    public const TYPE_NAME = 'wysiwyg';

    public function _construct()
    {
        parent::_construct();

        $this->options['title'] = __('Wysiwyg');
    }

    public function generateContent()
    {
        return '<div class="amform-wysiwyg-icon"></div>';
    }
}
