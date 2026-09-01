<?php
/**
 * Copyright © All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\CategoryQuestWL\Controller\Adminhtml\Get;

use Amasty\Customform\Model\AnswerFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Backend\App\Action\Context;

class Response extends \Magento\Backend\App\Action
{
    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var AnswerFactory
     */
    protected $answerFactory;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param AnswerFactory $answerFactory
     * @param JsonFactory $jsonFactory
     * @param FormKey $formKey
     */
    public function __construct(
        Context $context,
        AnswerFactory $answerFactory,
        JsonFactory $jsonFactory,
        FormKey $formKey
    ) {
        $this->answerFactory = $answerFactory;
        $this->jsonFactory = $jsonFactory;
        $this->formKey = $formKey;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $request = $this->getRequest();
        $uniqueId = $request->getParam('questionnaire_unique_id');
        $formId = (int) $request->getParam('form_id');
        $submittedFormKey = $request->getParam('form_key');

        // Validate form key for security
        if ($this->formKey->getFormKey() !== $submittedFormKey) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid Form Key. Please refresh the page and retry.')
            ]);
        }

        if (!$uniqueId) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Questionnaire unique ID is required.')
            ]);
        }

        // Load answer by unique ID and optionally form ID
        $answer = $this->getAnswerByUniqueId($uniqueId, $formId);
        if (!$answer || !$answer->getId()) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Answer not found for this unique ID.')
            ]);
        }

        return $resultJson->setData([
            'success' => true,
            'answer' => $answer->getData(),
            'unique_id' => $uniqueId
        ]);
    }

    /**
     * Get answer by unique ID and optional form ID
     *
     * @param string $uniqueId
     * @param int|null $formId
     * @return \Amasty\Customform\Model\Answer|null
     */
    protected function getAnswerByUniqueId(string $uniqueId, ?int $formId = null)
    {
        $collection = $this->answerFactory->create()
            ->getCollection()
            ->addFieldToFilter('questionnaire_unique_id', $uniqueId);

        if ($formId) {
            $collection->addFieldToFilter('form_id', $formId);
        }

        return $collection->getFirstItem();
    }
}
