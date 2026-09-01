<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Api\Data;

interface EntityInterface
{

    const IDENTITY_IMAGE = 'identity_image';
    const CUSTOMER_ID = 'customer_id';
    const FULL_IMAGE = 'full_image';
    const ENTITY_ID = 'entity_id';
    const ORDER_ID = 'order_id';
    const FULL_IMAGE2 = 'full_image2';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Kdi\ImageUpload\Entity\Api\Data\EntityInterface
     */
    public function setEntityId($entityId);

    /**
     * Get full_image
     * @return string|null
     */
    public function getFullImage();

    /**
     * Set full_image
     * @param string $fullImage
     * @return \Kdi\ImageUpload\Entity\Api\Data\EntityInterface
     */
    public function setFullImage($fullImage);

    /**
     * Get identity_image
     * @return string|null
     */
    public function getIdentityImage();

    /**
     * Set identity_image
     * @param string $identityImage
     * @return \Kdi\ImageUpload\Entity\Api\Data\EntityInterface
     */
    public function setIdentityImage($identityImage);

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Kdi\ImageUpload\Entity\Api\Data\EntityInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get order_id
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     * @param string $orderId
     * @return \Kdi\ImageUpload\Entity\Api\Data\EntityInterface
     */
    public function setOrderId($orderId);

    /**
     * Get full_image2
     * @return string|null
     */
    public function getFullImage2();

    /**
     * Set full_image
     * @param string $fullImage2
     * @return \Kdi\ImageUpload\Entity\Api\Data\EntityInterface
     */
    public function setFullImage2($fullImage2);


}

