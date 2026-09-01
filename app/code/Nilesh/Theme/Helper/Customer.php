<?php

namespace Nilesh\Theme\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session;

class Customer extends AbstractHelper
{
    protected $session;

    public function __construct(Context $context, Session $session)
    {
        parent::__construct($context);
        $this->session = $session;
    }

    public function isLogIn()
    {
        if ($this->session->isLoggedIn()) {
        // Customer is logged in
            return true;
        }

        return false;
    }

}