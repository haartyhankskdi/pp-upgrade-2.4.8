<?php 
namespace Haartyhanks\CategoryQuest\Block;

use Magento\Framework\View\Element\Template;
use Haartyhanks\CategoryQuest\Helper\Session;

class Data extends Template
{
    protected $helper;

    public function __construct(
        Template\Context $context,
        Session $helper,
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