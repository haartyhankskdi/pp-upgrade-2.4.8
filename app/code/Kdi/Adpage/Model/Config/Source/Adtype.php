<?php
namespace Kdi\Adpage\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Adtype implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '',            'label' => __('-- Please Select --')],
            ['value' => 'google_ad',   'label' => __('Google Ad page')],
            ['value' => 'cms_page',    'label' => __('CMS Page')],
        ];
    }

    // Optional helper for array keyed by value if needed
    public function toArray()
    {
        $options = $this->toOptionArray();
        $result = [];
        foreach ($options as $opt) {
            $result[$opt['value']] = $opt['label'];
        }
        return $result;
    }
}
