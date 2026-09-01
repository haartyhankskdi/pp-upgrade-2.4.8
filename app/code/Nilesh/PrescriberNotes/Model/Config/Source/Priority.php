<?php 

namespace Nilesh\PrescriberNotes\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Priority implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            0 => [
                'label' => 'High',
                'value' => 'High'
            ],
            1 => [
                'label' => 'Medium',
                'value' => 'Medium'
            ],
            2 => [
                'label' => 'Low',
                'value' => 'Low'
            ],
        ];

        return $options;
    }
}