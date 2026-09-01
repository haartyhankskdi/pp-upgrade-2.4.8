<?php

namespace Amasty\Customform\Observer\Form;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface as Session;
use Haartyhanks\CategoryQuest\Helper\Data;

class GetStepConfirm implements ObserverInterface
{

    /**
     * @var Session
     */
    protected $session;

    protected $helper;

    public function __construct(
        Session $session,
        Data $helper
    )
    {
        $this->session = $session;
        $this->helper = $helper;
    }

    public function execute(Observer $observer)
    {
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