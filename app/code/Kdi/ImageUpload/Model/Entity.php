<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Model;

use Kdi\ImageUpload\Api\Data\EntityInterface;
use Magento\Framework\Model\AbstractModel;

class Entity extends AbstractModel implements EntityInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Kdi\ImageUpload\Model\ResourceModel\Entity::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getFullImage()
    {
        return $this->getData(self::FULL_IMAGE);
    }

    /**
     * @inheritDoc
     */
    public function setFullImage($fullImage)
    {
        return $this->setData(self::FULL_IMAGE, $fullImage);
    }

    /**
     * @inheritDoc
     */
    public function getIdentityImage()
    {
        return $this->getData(self::IDENTITY_IMAGE);
    }

    /**
     * @inheritDoc
     */
    public function setIdentityImage($identityImage)
    {
        return $this->setData(self::IDENTITY_IMAGE, $identityImage);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getFullImage2()
    {
        return $this->getData(self::FULL_IMAGE2);
    }

    /**
     * @inheritDoc
     */
    public function setFullImage2($fullImage2)
    {
        return $this->setData(self::FULL_IMAGE2, $fullImage2);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }
}

