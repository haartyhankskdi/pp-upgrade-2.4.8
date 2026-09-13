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

class Checkboxtwo extends Checkbox
{
    public function _construct()
    {
        parent::_construct();
        $this->options['title'] = __('Checkbox v.2');
    }

    public function getLabelClassName()
    {
        return 'class="amform-versiontwo-label"';
    }

    public function getBr()
    {
        return '';
    }
}
