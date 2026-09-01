<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Block\Index;
use Magento\Framework\Session\SessionManagerInterface;

class Index extends \Magento\Framework\View\Element\Template
{

    protected $request;

    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        SessionManagerInterface $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $request;
        $this->session = $session;
    }


    public function getOrderId(){
        return $this->request->getParam('order_id');
    }

    public function getOrderIdFromSession(){

        $data =  $this->session->getData('PHPSESSID');

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/image11.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" Message Log from Session storage " . print_r($data, true));

         return $this->session->getData('PHPSESSID');

    }

    public function getEntityId(){
        return $this->request->getParam('id');
    }


    
}

