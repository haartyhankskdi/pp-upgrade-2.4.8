<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Popup\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface EmailRepositoryInterface
{

    /**
     * Save Email
     * @param \Kdi\Popup\Api\Data\EmailInterface $email
     * @return \Kdi\Popup\Api\Data\EmailInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Kdi\Popup\Api\Data\EmailInterface $email
    );

    /**
     * Retrieve Email
     * @param string $emailId
     * @return \Kdi\Popup\Api\Data\EmailInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($emailId);

    /**
     * Retrieve Email matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Kdi\Popup\Api\Data\EmailSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Email
     * @param \Kdi\Popup\Api\Data\EmailInterface $email
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Kdi\Popup\Api\Data\EmailInterface $email
    );

    /**
     * Delete Email by ID
     * @param string $emailId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($emailId);
}

