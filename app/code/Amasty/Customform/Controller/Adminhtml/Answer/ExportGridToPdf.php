<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Adminhtml\Answer;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Answer\FinderInterface as AnswersFinderInterface;
use Amasty\Customform\Model\Answer\FinderInterfaceFactory as AnswersFinderInterfaceFactory;
use Amasty\Customform\Model\Export\SubmitedData\AnswerExporterFactory;
use Amasty\Customform\Model\ExternalLibsChecker;
use Amasty\Customform\Model\Response\ZipStreamOctetResponseFactory;
use Amasty\Customform\Model\Zip\ZipFileNameGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class ExportGridToPdf extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = ExportGridToCsv::ADMIN_RESOURCE;
    public const DEFAULT_FILE_PREFIX = 'submitted_data';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ZipStreamOctetResponseFactory
     */
    private $zipStreamOctetResponseFactory;

    /**
     * @var ZipFileNameGenerator
     */
    private $zipFileNameGenerator;

    /**
     * @var string
     */
    private $resultFilePrefix;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ExternalLibsChecker
     */
    private $externalLibsChecker;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var AnswerExporterFactory
     */
    private $answerExporterFactory;

    /**
     * @var AnswersFinderInterfaceFactory
     */
    private $answersFinderFactory;

    /**
     * @var AnswersFinderInterface
     */
    private $answersFinder;

    public function __construct(
        Context $context,
        AnswerExporterFactory $answerExporterFactory,
        ZipStreamOctetResponseFactory $zipStreamOctetResponseFactory,
        LoggerInterface $logger,
        ZipFileNameGenerator $zipFileNameGenerator,
        ExternalLibsChecker $externalLibsChecker,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AnswersFinderInterfaceFactory $answersFinderFactory,
        string $resultFilePrefix = self::DEFAULT_FILE_PREFIX
    ) {
        $this->zipStreamOctetResponseFactory = $zipStreamOctetResponseFactory;
        $this->answerExporterFactory = $answerExporterFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->answersFinderFactory = $answersFinderFactory;
        $this->zipFileNameGenerator = $zipFileNameGenerator;
        $this->externalLibsChecker = $externalLibsChecker;
        $this->resultFilePrefix = $resultFilePrefix;
        $this->filterBuilder = $filterBuilder;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            if ($this->getAnswersFinder()->isEmptyResult()) {
                throw new LocalizedException(__('No answers were selected for export'));
            }

            $this->externalLibsChecker->checkPdfDom();
            $this->externalLibsChecker->checkZipStream();
            $response = $this->zipStreamOctetResponseFactory->create();
            $response->setFileName($this->zipFileNameGenerator->generate($this->resultFilePrefix));
            $response->setZipContentSource($this->getResponseSource());

            return $response;
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while export answers. Check exception log for more details')
            );
            $this->logger->error($e);
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }

    private function getResponseSource(): iterable
    {
        $exporter = $this->answerExporterFactory->create(AnswerExporterFactory::TYPE_PDF);
        $requestedAnswersSource = $this->getAnswersFinder()->getResult();

        foreach ($exporter->exportMultiple($requestedAnswersSource) as $exportResult) {
            yield $exportResult->getName() => $exportResult->getRaw();
        }
    }

    private function getAnswersFinder(): AnswersFinderInterface
    {
        if ($this->answersFinder === null) {
            $this->answersFinder = $this->answersFinderFactory->create();
            $this->answersFinder->setSearchCriteria($this->getSearchCriteria());
        }

        return $this->answersFinder;
    }

    private function getSearchCriteria(): SearchCriteriaInterface
    {
        $request = $this->getRequest();
        $selected = $request->getParam(Filter::SELECTED_PARAM, []);
        $excluded = $request->getParam(Filter::EXCLUDED_PARAM, []);
        $selected = array_map('intval', (array) $selected);
        $excluded = array_map('intval', (array) $excluded);
        $filters = [];

        if (!empty($selected)) {
            $this->filterBuilder
                ->setField(AnswerInterface::ANSWER_ID)
                ->setConditionType('in')
                ->setValue($selected);

            $filters[] = $this->filterBuilder->create();
        }

        if (!empty($excluded)) {
            $this->filterBuilder
                ->setField(AnswerInterface::ANSWER_ID)
                ->setConditionType('nin')
                ->setValue($excluded);

            $filters[] = $this->filterBuilder->create();
        }

        return $this->searchCriteriaBuilder->addFilters($filters)->create();
    }
}
