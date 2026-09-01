<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberName\Api\Data;

interface PrescriberNameInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const STATUS = 'status';
    const PRESCRIBERNAME_ID = 'prescribername_id';
    const NAME = 'name';
    const COMMENT = 'Comment';

    /**
     * Get prescribername_id
     * @return string|null
     */
    public function getPrescribernameId();

    /**
     * Set prescribername_id
     * @param string $prescribernameId
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     */
    public function setPrescribernameId($prescribernameId);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     */
    public function setName($name);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Nilesh\PrescriberName\Api\Data\PrescriberNameExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Nilesh\PrescriberName\Api\Data\PrescriberNameExtensionInterface $extensionAttributes
    );

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     */
    public function setStatus($status);

    /**
     * Get Comment
     * @return string|null
     */
    public function getComment();

    /**
     * Set Comment
     * @param string $comment
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     */
    public function setComment($comment);
}

