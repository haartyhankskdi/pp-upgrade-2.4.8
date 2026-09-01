<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Popup\Model\Data;

use Kdi\Popup\Api\Data\EmailInterface;

class Email extends \Magento\Framework\Api\AbstractExtensibleObject implements EmailInterface
{

    /**
     * Get email_id
     * @return string|null
     */
    public function getEmailId()
    {
        return $this->_get(self::EMAIL_ID);
    }

    /**
     * Set email_id
     * @param string $emailId
     * @return \Kdi\Popup\Api\Data\EmailInterface
     */
    public function setEmailId($emailId)
    {
        return $this->setData(self::EMAIL_ID, $emailId);
    }

    /**
     * Get product_id
     * @return string|null
     */
    public function getProductId()
    {
        return $this->_get(self::PRODUCT_ID);
    }

    /**
     * Set product_id
     * @param string $productId
     * @return \Kdi\Popup\Api\Data\EmailInterface
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Kdi\Popup\Api\Data\EmailExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Kdi\Popup\Api\Data\EmailExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Kdi\Popup\Api\Data\EmailExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Kdi\Popup\Api\Data\EmailInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get customer_ip
     * @return string|null
     */
    public function getCustomerIp()
    {
        return $this->_get(self::CUSTOMER_IP);
    }

    /**
     * Set customer_ip
     * @param string $customerIp
     * @return \Kdi\Popup\Api\Data\EmailInterface
     */
    public function setCustomerIp($customerIp)
    {
        return $this->setData(self::CUSTOMER_IP, $customerIp);
    }
}

