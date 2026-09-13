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
use Mirasvit\Helpdesk\Api\Data\TemplateInterface;
use Mirasvit\Helpdesk\Api\Data\TemplateSearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\TemplateSearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Repository\TemplateRepositoryInterface;
use Mirasvit\Helpdesk\Model\TemplateFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Template as TemplateResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Template\CollectionFactory;

class TemplateRepository implements TemplateRepositoryInterface
{
    private $templateFactory;

    private $templateResource;

    private $collectionFactory;

    private $searchResultsFactory;

    public function __construct(
        TemplateFactory $templateFactory,
        TemplateResource $templateResource,
        CollectionFactory $collectionFactory,
        TemplateSearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->templateFactory      = $templateFactory;
        $this->templateResource     = $templateResource;
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function get(int $templateId): TemplateInterface
    {
        $template = $this->templateFactory->create();
        $this->templateResource->load($template, $templateId);

        if (!$template->getId()) {
            throw new NoSuchEntityException(__('Template with id "%1" does not exist.', $templateId));
        }

        return $template;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): TemplateSearchResultsInterface
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
}
