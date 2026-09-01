<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Popup\Api\Data;

interface EmailInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const PRODUCT_ID = 'product_id';
    const EMAIL_ID = 'email_id';
    const CUSTOMER_IP = 'customer_ip';
    const ENTITY_ID = 'entity_id';

    /**
     * Get email_id
     * @return string|null
     */
    public function getEmailId();

    /**
     * Set email_id
     * @param string $emailId
     * @return \Kdi\Popup\Api\Data\EmailInterface
     */
    public function setEmailId($emailId);

    /**
     * Get product_id
     * @return string|null
     */
    public function getProductId();

    /**
     * Set product_id
     * @param string $productId
     * @return \Kdi\Popup\Api\Data\EmailInterface
     */
    public function setProductId($productId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Kdi\Popup\Api\Data\EmailExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Kdi\Popup\Api\Data\EmailExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Kdi\Popup\Api\Data\EmailExtensionInterface $extensionAttributes
    );

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Kdi\Popup\Api\Data\EmailInterface
     */
    public function setEntityId($entityId);

    /**
     * Get customer_ip
     * @return string|null
     */
    public function getCustomerIp();

    /**
     * Set customer_ip
     * @param string $customerIp
     * @return \Kdi\Popup\Api\Data\EmailInterface
     */
    public function setCustomerIp($customerIp);
}

