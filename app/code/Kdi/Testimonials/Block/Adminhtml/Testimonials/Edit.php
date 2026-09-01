<?php
namespace Kdi\Testimonials\Block\Adminhtml\Testimonials;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;

class Edit extends Container
{
    protected $_coreRegistry;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_objectId   = 'id';
        $this->_blockGroup = 'kdi_testimonials';
        $this->_controller = 'adminhtml_testimonials';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Chapter'));
        $this->buttonList->update(
            'back',
            'onclick',
            "setLocation('" . $this->getBackUrl() . "')"
        );

        $this->buttonList->add(
            'save_and_continue',
            [
                'label' => __('Save & Continue'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => [
                            'event' => 'saveAndContinueEdit',
                            'target' => '#edit_form'
                        ]
                    ]
                ]
            ],
            -100
        );
    }

   
}
