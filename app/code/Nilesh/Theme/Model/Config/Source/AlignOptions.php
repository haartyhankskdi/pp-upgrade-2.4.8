<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\Theme\Model\Config\Source;

class AlignOptions implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [['value' => 'left', 'label' => __('Left')],['value' => 'right', 'label' => __('Right')]];
    }

    public function toArray()
    {
        return ['left' => __('Left'),'right' => __('Right')];
    }
}