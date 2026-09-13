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



namespace Mirasvit\Helpdesk\Model\ResourceModel;

use Mirasvit\Helpdesk\Api\Data\TicketInterface;

use Mirasvit\Helpdesk\Helper\DesktopNotification;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Ticket extends AbstractDb
{
    protected $desktopNotificationHelper;

    protected $context;

    protected $resourcePrefix;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $phpSerializer;

    public function __construct(
        DesktopNotification                              $desktopNotificationHelper,
        \Psr\Log\LoggerInterface                         $logger,
        \Magento\Framework\Serialize\Serializer\Serialize $phpSerializer,
        Context                                          $context,
                                                         $resourcePrefix = null
    ) {
        $this->desktopNotificationHelper = $desktopNotificationHelper;
        $this->logger                    = $logger;
        $this->phpSerializer             = $phpSerializer;
        $this->context                   = $context;
        $this->resourcePrefix            = $resourcePrefix;
        parent::__construct($context, $resourcePrefix);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('mst_helpdesk_ticket', 'ticket_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return \Magento\Framework\Model\AbstractModel|\Mirasvit\Helpdesk\Model\Ticket
     */
    public function loadTagIds(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $select = $connection->select()
            ->from($this->getTable('mst_helpdesk_ticket_tag'))
            ->where('tt_ticket_id = ?', $object->getId());
        $array = [];
        if ($data = $connection->fetchAll($select)) {
            foreach ($data as $row) {
                $array[] = $row['tt_tag_id'];
            }
        }
        $object->setData('tag_ids', $array);

        return $object;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket $object
     *
     * @return void
     */
    protected function saveTagIds($object)
    {
        /* @var  \Mirasvit\Helpdesk\Model\Ticket $object */
        $connection = $this->getConnection();
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $condition = $connection->quoteInto('tt_ticket_id = ?', $object->getId());
        $connection->delete($this->getTable('mst_helpdesk_ticket_tag'), $condition);
        foreach ((array) $object->getData('tag_ids') as $id) {
            $objArray = [
                'tt_ticket_id' => $object->getId(),
                'tt_tag_id' => $id,
            ];
            $connection->insert(
                $this->getTable('mst_helpdesk_ticket_tag'),
                $objArray
            );
        }
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var  \Mirasvit\Helpdesk\Model\Ticket $object */
        if (!$object->getIsMassDelete()) {
            $this->loadTagIds($object);
        }
        $channelData = $object->getChannelData();
        if (is_string($channelData) && $channelData !== '') {
            try {
                /** @var array $decoded */
                $decoded = $this->getSerializer()->unserialize($channelData);
                $object->setChannelData($decoded);
            } catch (\Exception $e) {
                $recovered = false;
                if (strpos($channelData, 'a:') === 0) {
                    try {
                        $maybe = $this->phpSerializer->unserialize($channelData);
                        if (is_array($maybe)) {
                            $object->setChannelData($maybe);
                            $recovered = true;
                        }
                    } catch (\Exception $e2) {
                        // fall through
                    }
                }
                if (!$recovered) {
                    $this->logger->debug(
                        'Mirasvit\\Helpdesk Ticket: legacy non-JSON channel_data cannot be decoded. '
                        . substr($channelData, 0, 4096),
                        ['exception' => $e]
                    );
                }
            }
        }

        return parent::_afterLoad($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Mirasvit\Helpdesk\Model\Ticket $object */
        $object->isNew = false;
        if (!$object->getId() && !$object->getInTest() && !$object->getIsMigration()) {
            $time = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            $object->setCreatedAt($time);
            $object->setLastReplyAt($time);
            $object->isNew = true;
        }
        if (is_array($object->getChannelData())) {
            $object->setChannelData((string)$this->getSerializer()->serialize($object->getChannelData()));
        }
        if (!$object->getInTest() && !$object->getIsMigration()) {
            $object->setUpdatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }

        $tags = [];
        foreach ($object->getTags() as $tag) {
            $tags[] = $tag->getName();
        }
        $object->addToSearchIndex(implode(' ', $tags));

        return parent::_beforeSave($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var  \Mirasvit\Helpdesk\Model\Ticket $object */
        if (!$object->getIsMassStatus()) {
            $this->saveTagIds($object);
        }
        if ($object->isNew) {
            $this->desktopNotificationHelper->onTicketCreated($object);
        }

        return parent::_afterSave($object);
    }

    /************************/
    /**
     * Unlink a deleted customer from their tickets, keeping the tickets.
     *
     * A ticket is the record of a support conversation and has to survive the account: the module
     * already keeps the identical record for a ticket opened by someone with no account at all, which
     * is why customer_id is nullable while customer_email is NOT NULL. The email is what identifies
     * the requester, and it stays.
     *
     * @return int the number of tickets unlinked
     */
    public function detachCustomer(int $customerId): int
    {
        if ($customerId <= 0) {
            return 0;
        }

        $connection = $this->getConnection();
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */

        return $connection->update(
            $this->getMainTable(),
            [TicketInterface::KEY_CUSTOMER_ID => null],
            [TicketInterface::KEY_CUSTOMER_ID . ' = ?' => $customerId]
        );
    }

    /**
     * Unlink a deleted order from the tickets that referenced it.
     *
     * The conversation is about far more than the order link - subject, messages, satisfaction - so
     * the ticket stays and only the reference goes.
     *
     * @return int the number of tickets unlinked
     */
    public function detachOrder(int $orderId): int
    {
        if ($orderId <= 0) {
            return 0;
        }

        $connection = $this->getConnection();
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */

        return $connection->update(
            $this->getMainTable(),
            [TicketInterface::KEY_ORDER_ID => null],
            [TicketInterface::KEY_ORDER_ID . ' = ?' => $orderId]
        );
    }
}
