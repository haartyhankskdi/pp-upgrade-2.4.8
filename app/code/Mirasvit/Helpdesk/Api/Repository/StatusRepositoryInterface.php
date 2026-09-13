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



namespace Mirasvit\Helpdesk\Api\Repository;

use Magento\Framework\Api\SearchCriteriaInterface;
use Mirasvit\Helpdesk\Api\Data\StatusInterface;
use Mirasvit\Helpdesk\Api\Data\StatusSearchResultsInterface;

interface StatusRepositoryInterface
{
    /**
     * Save a helpdesk status
     *
     * @param \Mirasvit\Helpdesk\Api\Data\StatusInterface $status
     * @return \Mirasvit\Helpdesk\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(StatusInterface $status): StatusInterface;

    /**
     * Get a helpdesk status by ID
     *
     * @param int $statusId
     * @return \Mirasvit\Helpdesk\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $statusId): StatusInterface;

    /**
     * List helpdesk statuses
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mirasvit\Helpdesk\Api\Data\StatusSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): StatusSearchResultsInterface;
}
