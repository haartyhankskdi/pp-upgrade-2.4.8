<?php
namespace Kdi\Pharmacist\Block\Adminhtml\Pharmacist;

use Magento\Backend\Block\Widget\Form\Container;

class Edit extends \Magento\Backend\Block\Template
{
    protected $_template = 'Kdi_Pharmacist::pharmacist/edit.phtml';

    public function getFormAction()
    {
        return $this->getUrl('pharmamenu/pharmacist/save'); // URL for form submission
    }
}
