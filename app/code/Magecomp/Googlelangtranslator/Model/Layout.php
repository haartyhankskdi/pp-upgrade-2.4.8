<?php
namespace Magecomp\Googlelangtranslator\Model;

class Layout {

    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = array(
                array('label' => 'Simple Layout', 'value' => 'SIMPLE'),
                array('label' => 'Verticle Layout', 'value' => 'VERTICLE'),
                array('label' => 'Horizontal Layout', 'value' => 'HORIZONTAL'),
            );
        }
        return $this->_options;
    }
}