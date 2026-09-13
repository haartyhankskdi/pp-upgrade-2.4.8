<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model;

use Amasty\Base\Model\GetCustomerIp;
use Amasty\Customform\Api\Data\AnswerInterface;
use Magento\Customer\Model\Context;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;

class SurveyAvailableResolver
{
    /**
     * @var AnswerRepository
     */
    private $answerRepository;

    /**
     * @var FilterFactory
     */
    private $filterFactory;

    /**
     * @var FilterGroupFactory
     */
    private $filterGroupFactory;

    /**
     * @var SessionFactory
     */
    private $customerSessionFactory;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetCustomerIp
     */
    private $customerIp;

    public function __construct(
        AnswerRepository $answerRepository,
        FilterFactory $filterFactory,
        FilterGroupFactory $filterGroupFactory,
        SessionFactory $customerSessionFactory,
        HttpContext $httpContext,
        ManagerInterface $messageManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetCustomerIp $customerIp
    ) {

        $this->answerRepository = $answerRepository;
        $this->filterFactory = $filterFactory;
        $this->filterGroupFactory = $filterGroupFactory;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->httpContext = $httpContext;
        $this->messageManager = $messageManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerIp = $customerIp;
    }

    public function isSurveyAvailable(int $formId): bool
    {
        try {
            $list = $this->answerRepository->getListFilter($this->prepareSearchCriteria($formId));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $list = [];
        }

        return !count($list);
    }

    private function prepareSearchCriteria(int $formId): SearchCriteria
    {
        $filter = $this->filterFactory->create()->setField(AnswerInterface::FORM_ID)
            ->setValue($formId)
            ->setConditionType('eq');
        $filterGroup1 = $this->filterGroupFactory->create()->setFilters([$filter]);

        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            $filters[] = $this->filterFactory->create()->setField(AnswerInterface::CUSTOMER_ID)
                ->setValue($this->customerSessionFactory->create()->getId())
                ->setConditionType('eq');
        }
        $filters[] = $this->filterFactory->create()->setField(AnswerInterface::IP)
            ->setValue($this->customerIp->getCurrentIp())
            ->setConditionType('eq');
        $filterGroup2 = $this->filterGroupFactory->create()->setFilters($filters);
        $this->searchCriteriaBuilder->setFilterGroups([$filterGroup1, $filterGroup2]);

        return $this->searchCriteriaBuilder->create();
    }
}
