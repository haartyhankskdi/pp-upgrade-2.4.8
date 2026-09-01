<?php

namespace Nilesh\MirasvitHelpdeskCustomerImage\Block\Adminhtml;

use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Amasty\Customform\Model\ResourceModel\Answer\CollectionFactory as AnswerCollection;
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory as TicketCollection;
use Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory as MessageCollection;     


class HelpdeskImageTab extends \Magento\Backend\Block\Template implements TabInterface
{
    protected $_coreRegistry;

    /**
     * @var string
     */
    protected $_template = 'Nilesh_MirasvitHelpdeskCustomerImage::tab/helpdesk_image_tab.phtml';
    protected $orderCollection;
    protected $answerCollection;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory $messageFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Attachment\CollectionFactory $attachmentCollectionFactory,
        OrderCollectionFactory $orderCollection,
        AnswerCollection $answerCollection,
        TicketCollection $ticketCollection,
        MessageCollection $messageCollection,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        $this->messageFactory = $messageFactory;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->orderCollection = $orderCollection;
        $this->answerCollection = $answerCollection;
        $this->ticketCollection = $ticketCollection;
        $this->messageCollection = $messageCollection;
        parent::__construct($context, $data);
    }


    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Prescribers Upload');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Prescribers Upload');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    // public function getAllHelpdeskImagesByCustomerId()
    // {
        // $customerId = $this->getCustomerId();
        // if(!$customerId) return [];
        /*$ticket = $this->attachmentCollectionFactory->create()
              ->addFieldToFilter('customer_id', $customerId);

        $select = $ticket->getSelect();

        $select->joinLeft(
            ['message' => "mst_helpdesk_message"],
            'main_table.message_id = message.message_id'
        );

        $select->joinLeft(
            ['ticket' => "mst_helpdesk_ticket"],
            'message.ticket_id = ticket.ticket_id'
        );

        return $select;*/
        // $message = $this->messageFactory->create();
        // return $message->addFieldToFilter('customer_id', $customerId);
    // }

    public function getAttachmentByMessageId($messageId)
    {
        $attachments = $this->attachmentCollectionFactory->create()->addFieldToFilter("message_id", $messageId);
        return $attachments;
    }

    public function getCustomerOrder()
    {
        $customerId = $this->getCustomerId(); // pass customer id
        $customerOrder = $this->orderCollection->create()
            ->addFieldToFilter('customer_id', $customerId);
        return $customerOrder;
    }

    public function getTicketByCustomerId()
    {
        $tickets = $this->ticketCollection->create()->addFieldToFilter('customer_id', $this->getCustomerId());
        return $tickets->getData();
    }

    public function getMsgIdByTicketId($ticketId)
    {
        $messages = $this->messageCollection->create()->addFieldToFilter('ticket_id', $ticketId);
        return $messages->getData();
    }

}
?>