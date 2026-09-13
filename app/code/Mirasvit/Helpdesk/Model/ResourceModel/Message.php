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

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Message extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\Context
     */
    protected $context;

    /**
     * @var string|null
     */
    protected $resourcePrefix;

    /**
     * @var \Mirasvit\Helpdesk\Helper\DesktopNotification
     */
    protected $desktopNotificationHelper;

    /**
     * @param \Mirasvit\Helpdesk\Helper\DesktopNotification     $desktopNotificationHelper
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string                                            $resourcePrefix
     */
    public function __construct(
        \Mirasvit\Helpdesk\Helper\DesktopNotification $desktopNotificationHelper,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $resourcePrefix = null
    ) {
        $this->desktopNotificationHelper = $desktopNotificationHelper;
        $this->context = $context;
        $this->resourcePrefix = $resourcePrefix;

        parent::__construct($context, $resourcePrefix);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('mst_helpdesk_message', 'message_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var  \Mirasvit\Helpdesk\Model\Message $object */
        if (!$object->getIsMassDelete()) {
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
        /** @var  \Mirasvit\Helpdesk\Model\Message $object */
        if (!$object->getId() && !$object->getIsMigration()) {
            $object->setCreatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
            $object->isNew = true;
        }
        $object->setUpdatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

        return parent::_beforeSave($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var  \Mirasvit\Helpdesk\Model\Message $object */
        if (!$object->getIsMassStatus()) {
        }
        if ($object->isNew) {
            $this->desktopNotificationHelper->onMessageCreated($object);
        }

        return parent::_afterSave($object);
    }

    /************************/
    /**
     * Unlink a deleted customer from their messages, keeping the messages.
     *
     * Same reasoning as the ticket: a message is part of a conversation that has to stay readable,
     * customer_id is nullable because messages arrive from people with no account, and
     * customer_email is NOT NULL and carries the identity.
     *
     * @return int the number of messages unlinked
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
            ['customer_id' => null],
            ['customer_id = ?' => $customerId]
        );
    }
}
