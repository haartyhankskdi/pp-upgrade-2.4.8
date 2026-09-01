<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\Theme\Model\Config\Source;

class ContainerOptions implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [['value' => 'two', 'label' => __('two')],['value' => 'three', 'label' => __('three')]];
    }

    public function toArray()
    {
        return ['two' => __('two'),'three' => __('three')];
    }
}