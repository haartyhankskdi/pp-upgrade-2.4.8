<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Repository;

use Amasty\Reports\Api\Data\NotificationInterface;
use Amasty\Reports\Api\NotificationRepositoryInterface;
use Amasty\Reports\Model\ResourceModel\Notification as NotificationResource;
use Amasty\Reports\Model\ResourceModel\Notification\CollectionFactory;
use Amasty\Reports\Model\ResourceModel\Notification\Collection;
use Amasty\Reports\Model\NotificationFactory;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Api\Data\BookmarkSearchResultsInterfaceFactory;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * @var BookmarkSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var NotificationFactory
     */
    private $notificationFactory;

    /**
     * @var NotificationResource
     */
    private $notificationResource;

    /**
     * Model data storage
     *
     * @var array
     */
    private $notifications = [];

    /**
     * @var CollectionFactory
     */
    private $notificationCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        BookmarkSearchResultsInterfaceFactory $searchResultsFactory,
        NotificationFactory $notificationFactory,
        NotificationResource $notificationResource,
        CollectionFactory $notificationCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->notificationFactory = $notificationFactory;
        $this->notificationResource = $notificationResource;
        $this->notificationCollectionFactory = $notificationCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function save(NotificationInterface $notification): NotificationInterface
    {
        $entityId = (int)$notification->getEntityId();
        try {
            if ($entityId) {
                $notification = $this->getById($entityId)->addData($notification->getData());
            }
            $this->notificationResource->save($notification);
            unset($this->notifications[$entityId]);
        } catch (\Exception $e) {
            if ($entityId) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save notification with ID %1. Error: %2',
                        [$entityId, $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new notification. Error: %1', $e->getMessage()));
        }

        return $notification;
    }

    public function getNewNotification(): NotificationInterface
    {
        return $this->notificationFactory->create();
    }

    public function getById(int $entityId): NotificationInterface
    {
        if (!isset($this->notifications[$entityId])) {
            /** @var \Amasty\Reports\Model\Notification $notification */
            $notification = $this->notificationFactory->create();
            $this->notificationResource->load($notification, $entityId);
            if (!$notification->getEntityId()) {
                throw new NoSuchEntityException(__('Notification with specified ID "%1" not found.', $entityId));
            }
            $this->notifications[$entityId] = $notification;
        }

        return $this->notifications[$entityId];
    }

    public function delete(NotificationInterface $notification): bool
    {
        try {
            $this->notificationResource->delete($notification);
            unset($this->notifications[$notification->getEntityId()]);
        } catch (\Exception $e) {
            if ($notification->getEntityId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove notification with ID %1. Error: %2',
                        [$notification->getEntityId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove notification. Error: %1', $e->getMessage()));
        }

        return true;
    }

    public function deleteById(int $entityId): bool
    {
        $notificationModel = $this->getById($entityId);
        $this->delete($notificationModel);

        return true;
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Amasty\Reports\Model\ResourceModel\Notification\Collection $notificationCollection */
        $notificationCollection = $this->notificationCollectionFactory->create();

        // Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $notificationCollection);
        }

        $searchResults->setTotalCount($notificationCollection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();

        if ($sortOrders) {
            $this->addOrderToCollection($sortOrders, $notificationCollection);
        }

        $notificationCollection->setCurPage($searchCriteria->getCurrentPage());
        $notificationCollection->setPageSize($searchCriteria->getPageSize());

        $notifications = [];
        /** @var NotificationInterface $notification */
        foreach ($notificationCollection->getItems() as $notification) {
            $notifications[] = $this->getById($notification->getEntityId());
        }

        $searchResults->setItems($notifications);

        return $searchResults;
    }

    public function getAll(): \Amasty\Reports\Model\ResourceModel\Notification\Collection
    {
        return $this->notificationCollectionFactory->create();
    }

    private function addFilterGroupToCollection(FilterGroup $filterGroup, Collection $notificationCollection)
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $notificationCollection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }

    private function addOrderToCollection($sortOrders, Collection $notificationCollection)
    {
        /** @var SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $notificationCollection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_DESC) ? SortOrder::SORT_DESC : SortOrder::SORT_ASC
            );
        }
    }
}
