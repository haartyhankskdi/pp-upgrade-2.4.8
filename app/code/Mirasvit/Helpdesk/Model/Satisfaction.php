<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Model;

use Magento\Framework\DataObject\IdentityInterface;

/**
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction\Collection|\Mirasvit\Helpdesk\Model\Satisfaction[] getCollection()
 * @method \Mirasvit\Helpdesk\Model\Satisfaction load(int $id)
 * @method bool getIsMassDelete()
 * @method \Mirasvit\Helpdesk\Model\Satisfaction setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method \Mirasvit\Helpdesk\Model\Satisfaction setIsMassStatus(bool $flag)
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction getResource()
 */
class Satisfaction extends \Magento\Framework\Model\AbstractModel implements IdentityInterface, \Mirasvit\Helpdesk\Api\Data\SatisfactionInterface
{
    const CACHE_TAG = 'helpdesk_satisfaction';

    /**
     * @var string
     */
    protected $_cacheTag = 'helpdesk_satisfaction';

    /**
     * @var string
     */
    protected $_eventPrefix = 'helpdesk_satisfaction';

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    /**
     * @var \Mirasvit\Helpdesk\Model\MessageFactory
     */
    protected $messageFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\TicketFactory
     */
    protected $ticketFactory;

    /**
     * @var \Magento\Framework\Model\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Model\ResourceModel\AbstractResource|null
     */
    protected $resource;

    /**
     * @var \Magento\Framework\Data\Collection\AbstractDb|null
     */
    protected $resourceCollection;

    /**
     * @param \Mirasvit\Helpdesk\Model\MessageFactory                 $messageFactory
     * @param \Mirasvit\Helpdesk\Model\TicketFactory                  $ticketFactory
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     * @param array                                                   $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\MessageFactory $messageFactory,
        \Mirasvit\Helpdesk\Model\TicketFactory $ticketFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->messageFactory = $messageFactory;
        $this->ticketFactory = $ticketFactory;
        $this->context = $context;
        $this->registry = $registry;
        $this->resource = $resource;
        $this->resourceCollection = $resourceCollection;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Counstruct
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction');
    }

    /**
     * @param bool $emptyOption
     * @return array
     */
    public function toOptionArray($emptyOption = false)
    {
        return $this->getCollection()->toOptionArray($emptyOption);
    }

    /**
     * @var \Mirasvit\Helpdesk\Model\Message|null
     */
    protected $message = null;

    /**
     * @return \Mirasvit\Helpdesk\Model\Message|false
     */
    public function getMessage()
    {
        if (!$this->getMessageId()) {
            return false;
        }
        if ($this->message === null) {
            $this->message = $this->messageFactory->create()->load($this->getMessageId());
        }

        return $this->message;
    }

    /**
     * @var \Mirasvit\Helpdesk\Model\Ticket
     */
    protected $ticket = null;

    /**
     * @return \Mirasvit\Helpdesk\Model\Ticket|false
     */
    public function getTicket()
    {
        if (!$this->getTicketId()) {
            return false;
        }
        if ($this->ticket === null) {
            $this->ticket = $this->ticketFactory->create()->load($this->getTicketId());
        }

        return $this->ticket;
    }

    /************************/

    /**
     * @return int
     */
    public function getSatisfactionId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @param int $satisfactionId
     * @return $this
     */
    public function setSatisfactionId($satisfactionId)
    {
        return $this->setData(self::ID, $satisfactionId);
    }

    /**
     * @return int
     */
    public function getTicketId()
    {
        return $this->getData(self::KEY_TICKET_ID);
    }

    /**
     * @param int $ticketId
     * @return $this
     */
    public function setTicketId($ticketId)
    {
        return $this->setData(self::KEY_TICKET_ID, $ticketId);
    }

    /**
     * @return int
     */
    public function getMessageId()
    {
        return $this->getData(self::KEY_MESSAGE_ID);
    }

    /**
     * @param int $messageId
     * @return $this
     */
    public function setMessageId($messageId)
    {
        return $this->setData(self::KEY_MESSAGE_ID, $messageId);
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->getData(self::KEY_USER_ID);
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        return $this->setData(self::KEY_USER_ID, $userId);
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->getData(self::KEY_CUSTOMER_ID);
    }

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::KEY_CUSTOMER_ID, $customerId);
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->getData(self::KEY_STORE_ID);
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::KEY_STORE_ID, $storeId);
    }

    /**
     * @return int
     */
    public function getRate()
    {
        return $this->getData(self::KEY_RATE);
    }

    /**
     * @param int $rate
     * @return $this
     */
    public function setRate($rate)
    {
        return $this->setData(self::KEY_RATE, $rate);
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->getData(self::KEY_COMMENT);
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function setComment($comment)
    {
        return $this->setData(self::KEY_COMMENT, $comment);
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::KEY_CREATED_AT);
    }

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::KEY_UPDATED_AT);
    }

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::KEY_UPDATED_AT, $updatedAt);
    }
}
