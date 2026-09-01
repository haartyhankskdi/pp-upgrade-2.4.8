<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberName\Model\Data;

use Nilesh\PrescriberName\Api\Data\PrescriberNameInterface;

class PrescriberName extends \Magento\Framework\Api\AbstractExtensibleObject implements PrescriberNameInterface
{

    /**
     * Get prescribername_id
     * @return string|null
     */
    public function getPrescribernameId()
    {
        return $this->_get(self::PRESCRIBERNAME_ID);
    }

    /**
     * Set prescribername_id
     * @param string $prescribernameId
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     */
    public function setPrescribernameId($prescribernameId)
    {
        return $this->setData(self::PRESCRIBERNAME_ID, $prescribernameId);
    }

    /**
     * Get name
     * @return string|null
     */
    public function getName()
    {
        return $this->_get(self::NAME);
    }

    /**
     * Set name
     * @param string $name
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Nilesh\PrescriberName\Api\Data\PrescriberNameExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Nilesh\PrescriberName\Api\Data\PrescriberNameExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get status
     * @return string|null
     */
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * Set status
     * @param string $status
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get Comment
     * @return string|null
     */
    public function getComment()
    {
        return $this->_get(self::COMMENT);
    }

    /**
     * Set Comment
     * @param string $comment
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     */
    public function setComment($comment)
    {
        return $this->setData(self::COMMENT, $comment);
    }
}

