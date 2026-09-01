<?php
namespace Haartyhanks\CategoryQuest\Observer;

use Exception;
use Magento\Framework\Event\ObserverInterface;
use Haartyhanks\CategoryQuest\Helper\CustomCookie;
use Magento\Framework\Exception\InputException;
use Amasty\Customform\Helper\Data;
use Magento\Checkout\Model\Session as Session;


class SetItemCatQuestUniqueId implements ObserverInterface
{
    protected $customCookie;
    private $logger;
    protected $helperData;
    /**
     * @var Session
     */
    protected $session;

    public function __construct(
        CustomCookie $customCookie,
        \Psr\Log\LoggerInterface $logger,
        Session $session,
        Data $helperData
    )
    {
        $this->customCookie = $customCookie;
        $this->logger = $logger;
        $this->helperData = $helperData;
        $this->session = $session;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try{
            $item = $observer->getEvent()->getData('quote_item');
            $item = ( $item->getParentItem() ? $item->getParentItem() : $item );
            $item->setQuestionnaireUniqueId($this->session->getUniqueHashKey());
            // file_put_contents('/home/1584919.cloudwaysapps.com/hsccxvzyhq/public_html/test.txt', $this->session->setUniqueSingleHashKey()); 
            $item->getProduct()->setIsSuperMode(true);
        } catch (Exception $e)
        {   
            $this->logger->critical($e->getMessage());
        }
        
        

    }
}