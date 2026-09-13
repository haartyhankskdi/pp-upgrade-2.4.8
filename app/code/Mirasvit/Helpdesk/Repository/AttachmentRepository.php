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



namespace Mirasvit\Helpdesk\Repository;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirasvit\Helpdesk\Api\Data\AttachmentInterface;
use Mirasvit\Helpdesk\Api\Data\AttachmentBodyInterface;
use Mirasvit\Helpdesk\Api\Data\AttachmentBodyInterfaceFactory;
use Mirasvit\Helpdesk\Api\Data\AttachmentSearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\AttachmentSearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Repository\AttachmentRepositoryInterface;
use Mirasvit\Helpdesk\Model\AttachmentFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Attachment as AttachmentResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Attachment\CollectionFactory;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    private const TEXT_MIME_PREFIXES = [
        'text/',
        'application/json',
        'application/xml',
        'application/xhtml+xml',
        'application/javascript',
        'application/x-javascript',
        'application/csv',
    ];

    private $attachmentFactory;

    private $attachmentResource;

    private $collectionFactory;

    private $searchResultsFactory;

    private $bodyFactory;

    public function __construct(
        AttachmentFactory $attachmentFactory,
        AttachmentResource $attachmentResource,
        CollectionFactory $collectionFactory,
        AttachmentSearchResultsInterfaceFactory $searchResultsFactory,
        AttachmentBodyInterfaceFactory $bodyFactory
    ) {
        $this->attachmentFactory    = $attachmentFactory;
        $this->attachmentResource   = $attachmentResource;
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->bodyFactory          = $bodyFactory;
    }

    public function get(int $attachmentId): AttachmentInterface
    {
        $attachment = $this->attachmentFactory->create();
        $this->attachmentResource->load($attachment, $attachmentId);

        if (!$attachment->getId()) {
            throw new NoSuchEntityException(__('Attachment with id "%1" does not exist.', $attachmentId));
        }

        return $attachment;
    }

    public function getBody(int $attachmentId): AttachmentBodyInterface
    {
        /** @var \Mirasvit\Helpdesk\Model\Attachment $attachment */
        $attachment = $this->get($attachmentId);

        $rawBody  = $attachment->getBody();
        $mimeType = $attachment->getType();

        if ($this->isTextMimeType($mimeType)) {
            $content  = $rawBody;
            $encoding = 'plain';
        } else {
            $content  = base64_encode($rawBody);
            $encoding = 'base64';
        }

        return $this->bodyFactory->create(['data' => [
            'content'  => $content,
            'encoding' => $encoding,
            'name'     => $attachment->getName(),
            'type'     => $mimeType,
        ]]);
    }

    public function getList(SearchCriteriaInterface $searchCriteria): AttachmentSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $fields     = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() ?? [] as $filter) {
                $condition    = $filter->getConditionType() ?: 'eq';
                $fields[]     = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $direction = ($sortOrder->getDirection() === \Magento\Framework\Api\SortOrder::SORT_DESC)
                    ? 'DESC'
                    : 'ASC';
                $collection->setOrder($sortOrder->getField(), $direction);
            }
        }

        $totalCount = $collection->getSize();

        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($totalCount);

        return $searchResults;
    }

    private function isTextMimeType(string $mimeType): bool
    {
        $mimeType = strtolower($mimeType);

        foreach (self::TEXT_MIME_PREFIXES as $prefix) {
            if (strpos($mimeType, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }
}
