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
use Mirasvit\Helpdesk\Api\Data\AttachmentInterface;
use Mirasvit\Helpdesk\Api\Data\AttachmentBodyInterface;
use Mirasvit\Helpdesk\Api\Data\AttachmentSearchResultsInterface;

interface AttachmentRepositoryInterface
{
    /**
     * Get attachment by ID
     *
     * @param int $attachmentId
     * @return \Mirasvit\Helpdesk\Api\Data\AttachmentInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $attachmentId): AttachmentInterface;

    /**
     * Get attachment body with content and encoding info
     *
     * Returns plain text for text-based files and base64-encoded content for binary files.
     *
     * @param int $attachmentId
     * @return \Mirasvit\Helpdesk\Api\Data\AttachmentBodyInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBody(int $attachmentId): AttachmentBodyInterface;

    /**
     * List attachment metadata
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mirasvit\Helpdesk\Api\Data\AttachmentSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): AttachmentSearchResultsInterface;
}
