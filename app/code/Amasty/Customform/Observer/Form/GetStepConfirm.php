<?php

namespace Amasty\Customform\Observer\Form;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface as Session;
use Haartyhanks\CategoryQuest\Helper\Data;
use Haartyhanks\CategoryQuestWL\Helper\CustomCookie;

class GetStepConfirm implements ObserverInterface
{

    /**
     * @var Session
     */
    protected $session;

    protected $helper;

    protected $customCookie;

    public function __construct(
        Session $session,
        Data $helper,
        CustomCookie $customCookie
    )
    {
        $this->session = $session;
        $this->helper = $helper;
        $this->customCookie = $customCookie;
    }

    public function execute(Observer $observer)
    {
        // unique id get code for questionaire 01082024 open

        $event = $observer->getEvent();
        $customData = $event->getData('questionnaire_unique_id');
        $this->customCookie->set($customData, 3200);

        // unique id get code for questionaire 01082024 close

        $currentCategory = $this->helper->getCatValueSession();
        $isFilled = array(
            'category_questions_filled' => true,
            'category_id' => $currentCategory
        );
        $this->setIsFilledCategory($isFilled);
    }

    public function setIsFilledCategory($values){
        $this->session->start();
        return $this->session->setIsFilled($values);
    }

}