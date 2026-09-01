<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Controller\Adminhtml\Forms;

use Amasty\Customform\Api\Data\AnswerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class Export extends \Magento\Backend\App\Action
{
    public const AMASTY_CUSTOM_FORMS_EXPORT_PATH = 'amasty/custom_forms';

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var DirectoryList
     */
    protected $directory;

    /**
     * @var \Amasty\Customform\Model\AnswerRepository
     */
    protected $answerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    public function __construct(
        Action\Context $context,
        \Amasty\Customform\Model\AnswerRepository $answerRepository,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\Filesystem $filesystem,
        FileFactory $fileFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->json = $json;
        $this->filesystem = $filesystem;
        $this->answerRepository = $answerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $formId = (int)$this->getRequest()->getParam('form_id');
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('amasty_customform/forms/index');
        try {
            if (!$formId) {
                throw new NoSuchEntityException(__('Response was not found.'));
            }
            $this->searchCriteriaBuilder->addFilter('form_id', $formId);
            $answers = $this->answerRepository->getListFilter($this->searchCriteriaBuilder->create());
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addErrorMessage(__('This Response no longer exists.'));

            return $resultRedirect;
        }
        $fileData = [];
        if ($answers) {
            $fileData = $this->exportProcess($answers, $formId);
        } else {
            $this->messageManager->addErrorMessage(__('Submitted data was not found.'));
        }

        return $fileData
            ? $this->fileFactory->create('export_' . $formId . '.csv', $fileData, 'var')
            : $resultRedirect;
    }

    /**
     * @param array $answers
     * @param int $formId
     * @return array
     */
    private function exportProcess(array $answers, $formId)
    {
        try {
            $data = $this->prepareData($answers);
            $this->directory->create(self::AMASTY_CUSTOM_FORMS_EXPORT_PATH);
            $file = sprintf('export_%s.csv', $formId);
            $stream = $this->directory->openFile($file, 'w+');
            $stream->lock();
            foreach ($data as $row) {
                $stream->writeCsv($row);
            }
            $stream->unlock();
            $stream->close();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return [];
        }

        return ['type' => 'filename', 'value' => $file, 'rm' => true];
    }

    /**
     * @param AnswerInterface[] $answers
     * @return array
     */
    private function prepareData(array $answers): array
    {
        $allFields = $this->getAllFields($answers);
        $headersData = $this->getRow(current($answers), true) + $allFields;
        $answersData = $this->getAnswersData($answers, $allFields);

        return array_merge([$headersData], $answersData);
    }

    /**
     * Get data from answer, exclude fields data.
     *
     * @param AnswerInterface $answer
     * @param bool $isHeader
     * @return array
     */
    private function getRow(AnswerInterface $answer, $isHeader = false)
    {
        $result = [];
        foreach ($answer->getData() as $name => $value) {
            if (is_object($value)) {
                continue;
            }
            if ($name == AnswerInterface::RESPONSE_JSON) {
                continue;
            }
            $result[] = $isHeader ? ucwords(str_replace('_', ' ', $name)) : $value;
        }

        return $result;
    }

    /**
     * Collect all fields(include deleted/changed/etc fields from form) from answers.
     * @param AnswerInterface[] $answers
     * @return array
     */
    private function getAllFields(array $answers): array
    {
        $headers = [];
        foreach ($answers as $answer) {
            $value = $answer->getData(AnswerInterface::RESPONSE_JSON);
            $fields = $this->json->unserialize($value);
            foreach ($fields as $fieldId => $fieldData) {
                $headers[$fieldId] = $fieldData['label'];
            }
        }

        return array_unique($headers);
    }

    /**
     * Generate data for answer based on $allFields param.
     * If some field not exist in answer, add empty value.
     *
     * @param AnswerInterface $answer
     * @param array $fields
     * @return array
     */
    private function getFieldsRow(AnswerInterface $answer, array $fields): array
    {
        $fieldsRow = [];

        $value = $answer->getData(AnswerInterface::RESPONSE_JSON);
        $fieldsFromAnswer = $this->json->unserialize($value);
        foreach ($fields as $fieldId => $fieldLabel) {
            if (isset($fieldsFromAnswer[$fieldId])) {
                $fieldValue = $fieldsFromAnswer[$fieldId]['value'];
                $fieldsRow[] = is_array($fieldValue) ? implode(',', $fieldValue) : $fieldValue;
            } else {
                $fieldsRow[] = null;
            }
        }

        return $fieldsRow;
    }

    /**
     * @param AnswerInterface[] $answers
     * @param array $allFields
     * @return array
     */
    private function getAnswersData(array $answers, array $allFields): array
    {
        $resultValues = [];
        foreach ($answers as $answer) {
            $row = $this->getRow($answer);
            array_push(
                $row,
                ...$this->getFieldsRow($answer, $allFields)
            );
            $resultValues[$answer->getAnswerId()] = $row;
        }

        return $resultValues;
    }
}
