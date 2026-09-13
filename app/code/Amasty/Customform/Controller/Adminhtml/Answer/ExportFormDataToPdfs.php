<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Adminhtml\Answer;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Api\FormRepositoryInterface;
use Amasty\Customform\Model\Answer\FinderInterface as AnswersFinderInterface;
use Amasty\Customform\Model\Answer\FinderInterfaceFactory as AnswersFinderInterfaceFactory;
use Amasty\Customform\Model\Export\SubmitedData\AnswerExporterFactory;
use Amasty\Customform\Model\ExternalLibsChecker;
use Amasty\Customform\Model\Response\ZipStreamOctetResponseFactory;
use Amasty\Customform\Model\Zip\ZipFileNameGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class ExportFormDataToPdfs extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = ExportGridToCsv::ADMIN_RESOURCE;
    public const FORM_ID_PARAM = 'form_id';

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
     * @var ResultFactory
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

    /**
     * @var FormRepositoryInterface
     */
    private $formRepository;

    /**
     * @var FormInterface
     */
    private $currentForm;

    public function __construct(
        Context $context,
        AnswerExporterFactory $answerExporterFactory,
        ZipStreamOctetResponseFactory $zipStreamOctetResponseFactory,
        LoggerInterface $logger,
        ZipFileNameGenerator $zipFileNameGenerator,
        ExternalLibsChecker $externalLibsChecker,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AnswersFinderInterfaceFactory $answersFinderFactory,
        FormRepositoryInterface $formRepository,
        string $resultFilePrefix = ExportGridToPdf::DEFAULT_FILE_PREFIX
    ) {
        $this->zipStreamOctetResponseFactory = $zipStreamOctetResponseFactory;
        $this->answerExporterFactory = $answerExporterFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->answersFinderFactory = $answersFinderFactory;
        $this->zipFileNameGenerator = $zipFileNameGenerator;
        $this->externalLibsChecker = $externalLibsChecker;
        $this->resultFilePrefix = $resultFilePrefix;
        $this->formRepository = $formRepository;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            if ($this->getAnswersFinder()->isEmptyResult()) {
                throw new LocalizedException(__('Submitted data was not found.'));
            }

            $this->externalLibsChecker->checkPdfDom();
            $this->externalLibsChecker->checkZipStream();
            $response = $this->zipStreamOctetResponseFactory->create();
            $response->setFileName($this->zipFileNameGenerator->generate($this->getFilePrefix()));
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
        $resultRedirect->setRefererUrl();

        return $resultRedirect;
    }

    protected function getFilePrefix(): string
    {
        $form = $this->getCurrentForm();

        return $form === null ? $this->resultFilePrefix : $form->getCode();
    }

    private function getCurrentForm(): ?FormInterface
    {
        if ($this->currentForm === null) {
            try {
                $formId = (int) $this->getRequest()->getParam(self::FORM_ID_PARAM);
                $this->currentForm = $this->formRepository->get($formId);
            } catch (NoSuchEntityException $e) {
                $this->currentForm = null;
            }
        }

        return $this->currentForm;
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
        $currentForm = $this->getCurrentForm();
        $formId = $currentForm === null ? 0 : (int) $currentForm->getFormId();
        $this->searchCriteriaBuilder->addFilter(AnswerInterface::FORM_ID, $formId);

        return $this->searchCriteriaBuilder->create();
    }
}
