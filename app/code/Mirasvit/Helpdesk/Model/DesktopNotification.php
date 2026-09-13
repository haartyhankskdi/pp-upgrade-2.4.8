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

/**
 * @method int getTicketId()
 * @method $this setTicketId(int $param)
 * @method string getNotificationType()
 * @method $this setNotificationType(string $param)
 * @method string getSubject()
 * @method $this setSubject(string $param)
 */
class DesktopNotification extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var Ticket
     */
    protected $ticket = null;

    /**
     * @var TicketFactory
     */
    protected $ticketFactory = null;

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
     * @param TicketFactory $ticketFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        TicketFactory $ticketFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ticketFactory = $ticketFactory;
        $this->context = $context;
        $this->registry = $registry;
        $this->resource = $resource;
        $this->resourceCollection = $resourceCollection;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\ResourceModel\DesktopNotification');
    }

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

    /**
     * @return array
     */
    public function getReadByUserIds()
    {
        return explode(',', (string)$this->getData('read_by_user_ids'));
    }

    /**
     * @param array $ids
     * @return $this
     */
    public function setReadByUserIds($ids)
    {
        return $this->setData('read_by_user_ids', ','.implode(',', array_unique(array_filter($ids))).',');
    }

    /**
     * @param int $id
     * @return $this
     */
    public function addReadByUserId($id)
    {
        $ids = $this->getReadByUserIds();
        $ids[] = $id;
        $this->setReadByUserIds($ids);

        return $this;
    }
}