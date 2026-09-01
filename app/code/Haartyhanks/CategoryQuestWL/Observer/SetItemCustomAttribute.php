<?php
namespace Haartyhanks\Catalog\Setup\Observer;

use Magento\Framework\Event\ObserverInterface;

class SetItemCustomAttribute implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteItem = $observer->getQuoteItem();
        $product = $observer->getProduct();
        $quoteItem->setQuestionnaireUniqueId($product->getAttributeText('questionnaire_unique_id'));
    }

    public function getQuestionnaireValue()
    {
        $hashKeyJson = $this->getUniqueHassSession();
        $this->unsUniqueHassSession();
        
        if(empty($hashKeyJson) || $hashKeyJson == null){
            // echo "hash Key not found";
            // die;
            $hashKeyJson = $this->customCookie->get();
            $this->customCookie->delete();
        }
        return $hashKeyJson;
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