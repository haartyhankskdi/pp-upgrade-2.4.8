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



namespace Mirasvit\Helpdesk\Api\Data;

interface AttachmentInterface
{
    const TABLE_NAME = 'mst_helpdesk_attachment';

    const ID               = 'attachment_id';
    const KEY_MESSAGE_ID   = 'message_id';
    const KEY_EMAIL_ID     = 'email_id';
    const KEY_NAME         = 'name';
    const KEY_TYPE         = 'type';
    const KEY_SIZE         = 'size';
    const KEY_EXTERNAL_ID  = 'external_id';
    const KEY_STORAGE      = 'storage';

    /**
     * @return int
     */
    public function getAttachmentId();

    /**
     * @param int $attachmentId
     * @return $this
     */
    public function setAttachmentId($attachmentId);

    /**
     * @return int
     */
    public function getMessageId();

    /**
     * @param int $messageId
     * @return $this
     */
    public function setMessageId($messageId);

    /**
     * @return int
     */
    public function getEmailId();

    /**
     * @param int $emailId
     * @return $this
     */
    public function setEmailId($emailId);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type);

    /**
     * @return int
     */
    public function getSize();

    /**
     * @param int $size
     * @return $this
     */
    public function setSize($size);

    /**
     * @return string
     */
    public function getExternalId();

    /**
     * @param string $externalId
     * @return $this
     */
    public function setExternalId($externalId);

    /**
     * @return string
     */
    public function getStorage();

    /**
     * @param string $storage
     * @return $this
     */
    public function setStorage($storage);
}
