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



namespace Mirasvit\Helpdesk\Model;

use Magento\Framework\Model\AbstractModel;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\Helpdesk\Api\Data\ActivityInterface;

class Activity extends AbstractModel implements ActivityInterface
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(\Mirasvit\Helpdesk\Model\ResourceModel\Activity::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return $this->getData(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getExternalId(): string
    {
        return $this->getData(self::EXTERNAL_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setExternalId($value): ?Activity
    {
        return $this->setData(self::EXTERNAL_ID, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getKind(): string
    {
        return $this->getData(self::KIND);
    }

    /**
     * {@inheritdoc}
     */
    public function setKind($value): ?Activity
    {
        return $this->setData(self::KIND, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp(): string
    {
        return $this->getData(self::TIMESTAMP);
    }

    /**
     * {@inheritdoc}
     */
    public function setTimestamp($value): ?Activity
    {
        return $this->setData(self::TIMESTAMP, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId(): int
    {
        return $this->getData(self::USER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setUserId($value): ?Activity
    {
        return $this->setData(self::USER_ID, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(): string
    {
        return $this->getData(self::TITLE);
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($value): ?Activity
    {
        return $this->setData(self::TITLE, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return (string)$this->getData(self::DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($value): ?Activity
    {
        return $this->setData(self::DESCRIPTION, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(): string
    {
        return $this->getData(self::URL);
    }

    /**
     * {@inheritdoc}
     */
    public function setUrl($value): ?Activity
    {
        return $this->setData(self::URL, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload(): array
    {
        $data = $this->getData(self::PAYLOAD);

        return SerializeService::decode($data) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function setPayload($value): ?Activity
    {
        return $this->setData(self::PAYLOAD, SerializeService::encode($value));
    }
}
