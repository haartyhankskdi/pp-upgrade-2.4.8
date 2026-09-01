<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Model;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;

class PopupRepository implements \Magezon\PopupBuilder\Api\PopupRepositoryInterface
{
    /**
     * @var Popup[]
     */
    protected $instances = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magezon\PopupBuilder\Model\PopupFactory
     */
    protected $popupFactory;

    /**
     * @var \Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory
     */
    protected $popupCollectionFactory;

    /**
     * @var \Magezon\PopupBuilder\Model\ResourceModel\Popup
     */
    protected $popupResource;

    /**
     * @var \Magezon\PopupBuilder\Api\Data\PopupSearchResultsInterfaceFactory
     */
    protected $popupSearchResultsFactory;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface                        $storeManager
     * @param \Magezon\PopupBuilder\Model\PopupFactory                          $popupFactory
     * @param \Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory $popupCollectionFactory
     * @param \Magezon\PopupBuilder\Model\ResourceModel\Popup                   $popupResource
     * @param \Magezon\PopupBuilder\Api\Data\PopupSearchResultsInterfaceFactory $popupSearchResultsFactory
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magezon\PopupBuilder\Model\PopupFactory $popupFactory,
        \Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory $popupCollectionFactory,
        \Magezon\PopupBuilder\Model\ResourceModel\Popup $popupResource,
        \Magezon\PopupBuilder\Api\Data\PopupSearchResultsInterfaceFactory $popupSearchResultsFactory
    ) {
        $this->storeManager             = $storeManager;
        $this->popupFactory              = $popupFactory;
        $this->popupCollectionFactory    = $popupCollectionFactory;
        $this->popupResource             = $popupResource;
        $this->popupSearchResultsFactory = $popupSearchResultsFactory;
    }

    /**
     * Save popup.
     *
     * @param \Magezon\PopupBuilder\Api\Data\PopupInterface $popup
     * @return \Magezon\PopupBuilder\Api\Data\PopupInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Magezon\PopupBuilder\Api\Data\PopupInterface $popup)
    {
        $storeId = $popup->getStoreId();
        if (!$storeId) {
            $storeId = (int) $this->storeManager->getStore()->getId();
        }

        if ($popup->getId()) {
            $newData    = $popup->getData();
            $popup = $this->get($popup->getId(), $storeId);
            foreach ($newData as $k => $v) {
                $popup->setData($k, $v);
            }
        }

        try {
            $this->popupResource->save($popup);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __(
                    'Could not save popup: %1',
                    $e->getMessage()
                ),
                $e
            );
        }
        unset($this->instances[$popup->getId()]);
        return $this->get($popup->getId(), $storeId);
    }

    /**
     * Retrieve popup.
     *
     * @param int $popupId
     * @param int $storeId
     * @return \Magezon\PopupBuilder\Api\Data\PopupInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($popupId, $storeId = null)
    {
        $cacheKey = null !== $storeId ? $storeId : 'all';
        if (!isset($this->instances[$popupId][$cacheKey])) {
            /** @var Popup $popup */
            $popup = $this->popupFactory->create();
            if (null !== $storeId) {
                $popup->setStoreId($storeId);
            }
            $popup->load($popupId);

            if (!$popup->getId()) {
                throw NoSuchEntityException::singleField('id', $popupId);
            }
            $this->instances[$popupId][$cacheKey] = $popup;
        }
        return $this->instances[$popupId][$cacheKey];
    }

    /**
     * Delete popup.
     *
     * @param \Magezon\PopupBuilder\Api\Data\PopupInterface $popup
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Magezon\PopupBuilder\Api\Data\PopupInterface $popup)
    {
        try {
            $popupId = $popup->getId();
            $this->popupResource->delete($popup);
        } catch (\Exception $e) {
            throw new StateException(
                __(
                    'Cannot delete popup with id %1',
                    $popup->getId()
                ),
                $e
            );
        }
        unset($this->instances[$popupId]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($popupId)
    {
        $popup = $this->get($popupId);
        return  $this->delete($popup);
    }

    /**
     * Load popup data collection by given search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Magezon\PopupBuilder\Api\Data\PopupSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->popupSearchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Magezon\PopupBuilder\Model\ResourceModel\Popup\Collection $collection */
        $collection = $this->popupCollectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }

        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $popups = [];

        foreach ($collection as $popup) {
            $popups[] = $this->get($popup->getId());
        }
        $searchResults->setItems($popups);
        return $searchResults;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param \Magezon\PopupBuilder\Model\ResourceModel\Popup\Collection $collection
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magezon\PopupBuilder\Model\ResourceModel\Popup\Collection $collection
    ) {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $collection->addFieldToFilter($filter->getField(), $filter->getValue());
        }
    }
}
