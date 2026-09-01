<?php 
namespace Kdi\Consultation\Block;

use Magento\Framework\View\Element\Template;
use Kdi\GHQ\Helper\CustomSession;

class Data extends Template
{
    protected $helper;

    public function __construct(
        Template\Context $context,
        CustomSession $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function getHelper()
    {
        return $this->helper;
    }
}