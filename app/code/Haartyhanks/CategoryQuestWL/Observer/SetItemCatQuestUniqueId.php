<?php
namespace Haartyhanks\CategoryQuestWL\Observer;

use Magento\Framework\Event\ObserverInterface;
use Haartyhanks\CategoryQuestWL\Helper\CustomCookie;

class SetItemCatQuestUniqueId implements ObserverInterface
{
    protected $customCookie;

    public function __construct(CustomCookie $customCookie)
    {
        $this->customCookie = $customCookie;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $item = $observer->getEvent()->getData('quote_item');
        $item = ( $item->getParentItem() ? $item->getParentItem() : $item );
        $item->setQuestionnaireUniqueId($this->customCookie->get());
        $item->getProduct()->setIsSuperMode(true);
        // file_put_contents('/home/master/applications/bzhyrrvjxm/public_html/test.txt', $item->getProductType());

    }
}