<?php

namespace Haartyhanks\Catalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper as MagentoAbstractHelper;
use Magento\Checkout\Model\Session as Session;

class CustomSession extends MagentoAbstractHelper
{
    /**
     * @var Session
     */
    protected $session;

    public function __construct(
        Session $session
    )
    {
        $this->session = $session;
    }

    public function getUniqueHashSession(){
        $this->session->start();
        return $this->session->getUniqueHashKey();
    }

    public function setUniqueHashSession($value)
    {
        $this->session->start();
        $this->session->setUniqueHashKey($value);
    }

    public function unsUniqueHashSession(){
        $this->session->start();
        return $this->session->unsUniqueHashKey();
    }
}
?>