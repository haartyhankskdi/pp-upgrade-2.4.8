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
 * @package   mirasvit/module-report
 * @version   1.4.74
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\Report\Api\Repository;


use Magento\Framework\Exception\NoSuchEntityException;
use Mirasvit\Report\Api\Data\EmailInterface;

interface EmailRepositoryInterface
{
    /**
     * @return \Mirasvit\Report\Model\ResourceModel\Email\Collection|EmailInterface[]
     */
    public function getCollection();

    /**
     * @param EmailInterface $email
     * @return EmailInterface
     */
    public function save(EmailInterface $email);

    /**
     * @param int $id
     * @return EmailInterface|null
     */
    public function get($id);

    /**
     * @return EmailInterface
     */
    public function create();

    /**
     * @param EmailInterface $email
     * @return bool
     */
    public function delete(EmailInterface $email);

    /**
     * Get report blocks for emails.
     *
     * @return array
     */
    public function getReports();

    /**
     * Get email by ID.
     *
     * @param int $emailId
     * @return \Mirasvit\Report\Api\Data\EmailInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $emailId): \Mirasvit\Report\Api\Data\EmailInterface;

    /**
     * Get list of emails.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mirasvit\Report\Api\Data\EmailSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): \Mirasvit\Report\Api\Data\EmailSearchResultsInterface;

    /**
     * Save email via REST API.
     *
     * @param \Mirasvit\Report\Api\Data\EmailInterface $email
     * @param int|null $emailId
     * @return \Mirasvit\Report\Api\Data\EmailInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveEmail(\Mirasvit\Report\Api\Data\EmailInterface $email, ?int $emailId = null): \Mirasvit\Report\Api\Data\EmailInterface;

    /**
     * Delete email by ID.
     *
     * @param int $emailId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById(int $emailId): bool;
}
