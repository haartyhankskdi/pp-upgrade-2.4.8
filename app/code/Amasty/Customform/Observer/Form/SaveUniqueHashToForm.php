<?php

namespace Amasty\Customform\Observer\Form;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Amasty\Customform\Helper\Data as HelperData;
use Magento\Checkout\Model\Session as Session;
use Magento\Framework\Math\Random;

class SaveUniqueHashToForm implements ObserverInterface
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
     * @var Random
     */
    protected $random;

    public function __construct(
        // HelperData $helperData
        Session $session,
        Random $random
    )
    {
        // $this->helperData = $helperData;
        $this->session = $session;
        $this->random = $random;
    }

    public function setUniqueHash($value)
    {
        $this->session->start();
        $this->session->setUniqueHashKey($value);
    }
    public function getUniqueHash(){
        $this->session->start();
        return $this->session->getUniqueHashKey();
    }
    public function unsUniqueHash(){
        $this->session->start();
        return $this->session->unsUniqueHashKey();
    }
    public function getHashKey()
    {
        $hashKeyArray = [];
        $hashKey = $this->random->getUniqueHash();
        if($this->getUniqueHash()){
            $hashKeyArray = json_decode($this->getUniqueHash());
            array_push($hashKeyArray, $hashKey);
            $hashKeyJson = json_encode($hashKeyArray);
        } else {
            array_push($hashKeyArray, $hashKey);
            $hashKeyJson = json_encode($hashKeyArray);
        }
        $this->setUniqueHash($hashKeyJson);
        return $hashKey;
    }

    public function execute(Observer $observer)
    {
        
        $hashKey = $this->getHashKey();
        $answer= $observer->getData('amasty_customform_answer');
        $answer->setQuestionnaireUniqueId($hashKey);
        
        $answer->save();
    }
}