<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Adminhtml\Answer;

use Amasty\Customform\Api\AnswerRepositoryInterface;
use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Exceptions\ExternalDependencyNotFoundException;
use Amasty\Customform\Model\Export\SubmitedData\AnswerExporterFactory;
use Amasty\Customform\Model\Response\InMemoryFileResponseFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class ExportAnswerToPdf extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = ExportGridToCsv::ADMIN_RESOURCE;
    public const ANSWER_ID = 'id';

    /**
     * @var AnswerExporterFactory
     */
    private $answerExporterFactory;

    /**
     * @var AnswerRepositoryInterface
     */
    private $answerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InMemoryFileResponseFactory
     */
    private $fileResponseFactory;

    public function __construct(
        Context $context,
        AnswerExporterFactory $answerExporterFactory,
        AnswerRepositoryInterface $answerRepository,
        InMemoryFileResponseFactory $fileResponseFactory,
        LoggerInterface $logger
    ) {
        $this->answerExporterFactory = $answerExporterFactory;
        $this->fileResponseFactory = $fileResponseFactory;
        $this->answerRepository = $answerRepository;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $answer = $this->getRequestedAnswer();
            $exporter = $this->answerExporterFactory->create(AnswerExporterFactory::TYPE_PDF);
            $exportedPdf = $exporter->export($answer);
            $resultPdfResponse = $this->fileResponseFactory->create();
            $resultPdfResponse->setFileName($exportedPdf->getName());
            $resultPdfResponse->setContents($exportedPdf->getRaw());
            $resultPdfResponse->setContentType('application/pdf');
        } catch (ExternalDependencyNotFoundException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Requested answer was not found'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Something went wrong. Check logs for additional info');
            $this->logger->error($e);
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        return $resultPdfResponse ?? $resultRedirect;
    }

    private function getRequestedAnswer(): AnswerInterface
    {
        $answerId = (int) $this->getRequest()->getParam(self::ANSWER_ID);

        return $this->answerRepository->get($answerId);
    }
}
