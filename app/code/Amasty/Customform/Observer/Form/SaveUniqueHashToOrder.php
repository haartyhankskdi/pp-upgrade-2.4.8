<?php

namespace Amasty\Customform\Observer\Form;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Amasty\Customform\Helper\Data as HelperData;
use Magento\Framework\Session\SessionManagerInterface as Session;
use Haartyhanks\CategoryQuest\Helper\CustomCookie;


class SaveUniqueHashToOrder implements ObserverInterface
{
    // /**
    //  * @var HelperData
    //  */
    // protected $helperData;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CustomCookie
     */
    protected $customCookie;

    public function __construct(
        // HelperData $helperData
        Session $session,
        CustomCookie $customCookie
    )
    {
        // $this->helperData = $helperData;
        $this->session = $session;
        $this->customCookie = $customCookie;
    }

    public function execute(Observer $observer)
    {
        $hashKeyJson = $this->getUniqueHassSession();
        $this->unsUniqueHassSession();
        
        if(empty($hashKeyJson) || $hashKeyJson == null){
            // echo "hash Key not found";
            // die;
            $hashKeyJson = $this->customCookie->get();
            // $this->customCookie->delete();
        }
        $this->customCookie->delete();
        // }
        // $value = $this->helperData->getUniqueHash();
        // $this->helperData->unsUniqueHash();
        $order= $observer->getData('order');
        $order->setQuestionnaireUniqueId($hashKeyJson); 
        $order->save();
    }

    public function getUniqueHassSession(){
        $this->session->start();
        return $this->session->getUniqueHashKey();
    }
    public function unsUniqueHassSession(){
        $this->session->start();
        return $this->session->unsUniqueHashKey();
    }
}