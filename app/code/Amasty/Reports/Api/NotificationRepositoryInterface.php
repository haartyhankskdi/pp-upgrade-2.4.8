<?php

declare(strict_types=1);

namespace Amasty\Reports\Api;

use Amasty\Reports\Api\Data\NotificationInterface;

interface NotificationRepositoryInterface
{
    public function save(NotificationInterface $notification): NotificationInterface;

    public function getNewNotification(): NotificationInterface;

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $entityId): NotificationInterface;

    /**
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(NotificationInterface $notification): bool;

    /**
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Lists
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    public function getAll(): \Amasty\Reports\Model\ResourceModel\Notification\Collection;
}
