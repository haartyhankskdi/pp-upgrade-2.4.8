<?php

namespace Nilesh\Prefech\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class PreloadType extends Select
{
    /** @var array $_options */
    protected $_options = [
        'script'   => 'Script',
        'style'    => 'Style',
        'font'     => 'Font',
        'image'    => 'Image',
        'fetch'    => 'Fetch/XHR',
        'document' => 'Document'
    ];

    /**
     * PreloadType constructor.
     *
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_options as $value => $label) {
                $this->addOption($value, $label);
            }
        }

        $this->setClass('input-select required-entry');
        $this->setExtraParams('style="width: 100px;"');

        return parent::_toHtml();
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
