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

interface MessageItemInterface
{
    /**
     * @return int
     */
    public function getMessageId();

    /**
     * @return int
     */
    public function getTicketId();

    /**
     * @return int|null
     */
    public function getUserId();

    /**
     * @return int|null
     */
    public function getCustomerId();

    /**
     * @return string
     */
    public function getCustomerEmail();

    /**
     * @return string
     */
    public function getCustomerName();

    /**
     * @return string
     */
    public function getBody();

    /**
     * @return string
     */
    public function getBodyFormat();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getTriggeredBy();

    /**
     * @return int
     */
    public function getIsRead();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @return string|null
     */
    public function getUserName();

    /**
     * @return string
     */
    public function getThirdPartyEmail();

    /**
     * @return string
     */
    public function getThirdPartyName();

    /**
     * @return \Mirasvit\Helpdesk\Api\Data\AttachmentMetadataInterface[]
     */
    public function getAttachments();
}
