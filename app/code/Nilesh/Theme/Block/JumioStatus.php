<?php

namespace Nilesh\Theme\Block;

use Magento\Framework\View\Element\Template;
use Kdi\JumioVerification\Model\JumioVerificationFactory;

class JumioStatus extends Template
{
    protected $jumioVerificationFactory;

    public function __construct(
        Template\Context $context,
        JumioVerificationFactory $jumioVerificationFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->jumioVerificationFactory = $jumioVerificationFactory;
    }

    public function getVerificationCollection()
    {
        $verificationCollection = $this->jumioVerificationFactory->create()->getCollection();
        if (!$verificationCollection) {
            return null;
        }
        return $verificationCollection;
    }
}
