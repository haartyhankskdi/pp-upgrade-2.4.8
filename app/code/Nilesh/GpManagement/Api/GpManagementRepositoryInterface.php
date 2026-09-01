<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GpManagement\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface GpManagementRepositoryInterface
{

    /**
     * Save GpManagement
     * @param \Nilesh\GpManagement\Api\Data\GpManagementInterface $gpManagement
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Nilesh\GpManagement\Api\Data\GpManagementInterface $gpManagement
    );

    /**
     * Retrieve GpManagement
     * @param string $gpmanagementId
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($gpmanagementId);

    /**
     * Retrieve GpManagement matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Nilesh\GpManagement\Api\Data\GpManagementSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete GpManagement
     * @param \Nilesh\GpManagement\Api\Data\GpManagementInterface $gpManagement
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Nilesh\GpManagement\Api\Data\GpManagementInterface $gpManagement
    );

    /**
     * Delete GpManagement by ID
     * @param string $gpmanagementId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($gpmanagementId);
}

