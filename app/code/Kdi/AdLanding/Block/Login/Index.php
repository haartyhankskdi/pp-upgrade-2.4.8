<?php

declare(strict_types=1);

namespace Kdi\AdLanding\Block\Login;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Framework\View\Element\Template\Context;

class Index extends \Magento\Customer\Block\Form\Login
{
    public function __construct(
        Context $context,
        Session $customerSession,
        Url $customerUrl,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $customerUrl,
            $data
        );
    }

    /**
     * Get custom login post URL
     */
    public function getPostActionUrl(): string
    {
        return $this->getUrl('adlanding/login/post');
    }
}
