<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class Submit
{
    public const TYPE = 'type';
    public const LABEL = 'label';
    public const VALUE = 'value';

    /**
     * @var AnswerRepository
     */
    private $answerRepository;

    /**
     * @var AnswerFactory
     */
    private $answerFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var CachingFormProvider
     */
    private $formProvider;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $params;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * This is temporary solution.
     *
     * @var bool
     */
    private $canProcessSubmitFilesByAjax;

    public function __construct(
        CachingFormProvider $formProvider,
        AnswerRepository $answerRepository,
        AnswerFactory $answerFactory,
        Data $helper,
        Escaper $escaper,
        RedirectInterface $redirect,
        StoreManagerInterface $storeManager,
        SessionManagerInterface $session,
        Validator $formKeyValidator,
        RequestInterface $request,
        ManagerInterface $eventManager,
        TimezoneInterface $timezone,
        bool $canProcessSubmitFilesByAjax = false
    ) {
        $this->answerRepository = $answerRepository;
        $this->answerFactory = $answerFactory;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->escaper = $escaper;
        $this->redirect = $redirect;
        $this->formProvider = $formProvider;
        $this->session = $session;
        $this->formKeyValidator = $formKeyValidator;
        $this->request = $request;
        $this->eventManager = $eventManager;
        $this->timezone = $timezone;
        $this->canProcessSubmitFilesByAjax = $canProcessSubmitFilesByAjax;
    }

    public function process(array $params, ?AnswerInterface $answer = null): string
    {
        $this->params = $params;

        if ($this->validateData($params)) {
            /** @var Form $formModel */
            $formModel = $this->formProvider->getById((int) $params['form_id']);
            $model = $this->submit($formModel, $answer);

            if ($formModel->getEmailField()) {
                $params['email'] = $params[$formModel->getEmailField()] ?? '';
                $this->request->setParams($params);
            }

            $this->session->unsFormData();

            $url = $formModel->getSuccessUrl();

            if ($url && $url != '/') {
                $url = trim($url, '/');
            }

            $eventName = $answer
                ? 'amasty_custom_form_answer_edited'
                : 'amasty_custom_form_submitted';
            $this->eventManager->dispatch(
                $eventName,
                [
                    'answer' => $model,
                    'form' => $formModel
                ]
            );
        }

        return $url ?? Data::REDIRECT_PREVIOUS_PAGE;
    }

    public function submit(Form $formModel, ?AnswerInterface $answer = null)
    {
        /** @var  Answer $model */
        $model = $answer ?: $this->answerFactory->create();
        $answerData = $this->generateAnswerData($formModel, $answer);
        $model->addData($answerData);
        $model->setAdminResponseEmail($model->getRecipientEmail());
        $this->answerRepository->save($model);

        return $model;
    }

    /**
     * @param array $params
     * @return bool
     * @throws LocalizedException
     * @throws ValidatorException
     */
    private function validateData(array $params)
    {
        if (!isset($params['form_id'])) {
            throw new LocalizedException(__('form_id is not resolved.'));
        }

        if (!$this->isValidFormKey()) {
            throw new LocalizedException(
                __('Form key is not valid. Please try to reload the page.')
            );
        }

        if ($this->helper->isGDPREnabled() && isset($params['gdpr']) && !$params['gdpr']) {
            throw new LocalizedException(__('Please agree to the Privacy Policy'));
        }

        $fileFields = $this->request->getFiles();
        if ($fileFields && $fileFields->count()) {
            $this->validateFiles($fileFields->toArray());
        }

        return true;
    }

    public function isValidFormKey(): bool
    {
        return $this->formKeyValidator->validate($this->request);
    }

    /**
     * @param array $fileFields
     *
     * @throws LocalizedException
     * @throws ValidatorException
     */
    private function validateFiles($fileFields)
    {
        foreach ($fileFields as $files) {
            foreach ($files as $file) {
                $errorCode = $file['error'] ?? 0;

                switch ($errorCode) {
                    case UPLOAD_ERR_FORM_SIZE:
                    case UPLOAD_ERR_INI_SIZE:
                        $fileName = $file['name'] ?: '';
                        throw new LocalizedException(
                            __(
                                'File with name "%1" exceeds the allowed file size. Form was not submitted',
                                $fileName
                            )
                        );
                    case UPLOAD_ERR_CANT_WRITE:
                        throw new ValidatorException(__('File upload error. Failed to write file to disk'));
                    case UPLOAD_ERR_NO_TMP_DIR:
                        throw new ValidatorException(__('File upload error. Missing a temporary folder'));
                    case UPLOAD_ERR_PARTIAL:
                        throw new ValidatorException(
                            __('File upload error.  The uploaded file was only partially uploaded')
                        );
                }
            }
        }
    }

    private function generateAnswerData($formModel, ?AnswerInterface $answer = null)
    {
        $answerResponse = $this->generateAnswerResponse($formModel);
        $data = [
            'form_id' => $formModel->getId(),
            'ip' => $this->helper->getCurrentIp(),
            'customer_id' => (int) $this->helper->getCurrentCustomerId(),
            'questionnaire_unique_id' => $this->helper->getQuestionnaireUniqueId()
        ];

        if ($answer) {
            $answerResponse = $this->updateResponse($answerResponse, $answer);
        } else {
            $data['store_id'] = $this->storeManager->getStore()->getId();
        }

        $data['response_json'] = $this->helper->encode($answerResponse);

        if ($formModel->getSaveRefererUrl()) {
            $data['referer_url'] = $this->addRefererUrlIfNeed();
        }

        return $data;
    }

    private function updateResponse(array $answerResponse, AnswerInterface $answer): array
    {
        try {
            $oldResponse = $this->helper->decode($answer->getResponseJson());
        } catch (\Exception $e) {
            $oldResponse = [];
        }

        return array_replace($oldResponse, $answerResponse);
    }

    public function addRefererUrlIfNeed(): string
    {
        return $this->escaper->escapeUrl($this->redirect->getRefererUrl());
    }

    /**
     * @param FormInterface $formModel
     *
     * @return array
     * @throws LocalizedException
     */
    private function generateAnswerResponse($formModel): array
    {
        $formJson = $formModel->getFormJson();
        $pages = $this->helper->decode($formJson);
        $data = [];

        foreach ($pages as $page) {
            if (isset($page['type'])) {
                // support for old versions of forms
                $data = $this->dataProcessing($page, $data);
            } else {
                foreach ($page as $field) {
                    $data = $this->dataProcessing($field, $data);
                }
            }
        }
        if ($productId = isset($this->params['hide_product_id']) ? $this->params['hide_product_id'] : null) {
            $data['hide_product_id'] = [
                self::VALUE => $productId,
                self::LABEL => __('Requested Product'),
                self::TYPE => 'textinput'
            ];
        }

        return $data;
    }

    /**
     * @param $data
     * @param $record
     *
     * @return mixed
     * @throws LocalizedException
     */
    private function dataProcessing($data, $record)
    {
        $name = $data['name'];
        $value = $this->getValidValue($data, $name);
        if ($value) {
            $type = $data[self::TYPE];
            switch ($type) {
                case 'googlemap':
                    $record[$name][self::VALUE] = $value;
                    break;
                case 'checkbox':
                case 'checkboxtwo':
                case 'dropdown':
                case 'listbox':
                case 'radio':
                case 'radiotwo':
                    $tmpValue = [];

                    foreach ($data['values'] as $option) {
                        if (is_array($value) && in_array($option[self::VALUE], $value)) {
                            $tmpValue[] = $option[self::LABEL];
                        } elseif ($value == $option[self::VALUE]) {
                            $tmpValue[] = $option[self::LABEL];
                            break;
                        }
                    }

                    $record[$name][self::VALUE] = $tmpValue ? implode(', ', $tmpValue) : $value;
                    break;
                default:
                    $value = $this->helper->escapeHtml($value);
                    $record[$name][self::VALUE] = $value;
            }

            $record[$name][self::LABEL] = $data[self::LABEL];
            $record[$name][self::TYPE] = $type;
        }

        return $record;
    }

    /**
     * @param $field
     * @param $name
     * @return array|mixed
     * @throws LocalizedException
     */
    private function getValidValue($field, $name)
    {
        $result = $this->params[$name] ?? '';
        $fileValidation = [];
        $validation = $this->helper->generateValidation($field);
        $fieldType = $this->getRow($field, 'type');
        $isFile = strcmp($fieldType, 'file') === 0;
        $isMultiple = (bool)$this->getRow($field, 'multiple');
        $filesArray = $isFile ? $this->request->getFiles()->toArray() : [];
        $isFilesEmpty = $isFile ? $this->isEmptyFiles($filesArray, $name, $isMultiple) : false;

        if ($validation) {
            $valueNotExist = (!$isFile && !$result) || ($isFile && $isFilesEmpty);

            if (!array_key_exists('required', $validation) && $valueNotExist) {
                return $result;
            }

            $this->validateField($field, $fieldType, $validation, $result, $fileValidation);
        }

        if ($fieldType == 'googlemap' && $result) {
            $coordinates = explode(', ', trim($result, '()'));

            if (!isset($coordinates[0]) || !isset($coordinates[1])) {
                $coordinates = [0, 0];
            }

            $result = $this->helper->encode(
                [
                    'position' => [
                        'lat' => (float)$coordinates[0],
                        'lng' => (float)$coordinates[1]
                    ],
                    'zoom' => (int)$field['zoom']
                ]
            );
        }

        $canSubmitFilesByAjax = $this->canProcessSubmitFilesByAjax && !empty($filesArray);

        if ($isFile
            && !$isFilesEmpty
            && (!$this->request->isAjax() || $canSubmitFilesByAjax)
            && !$this->isHiddenField($field)
        ) {
            if ($isMultiple) {
                $result = [];
                $filesFromRequest = $this->request->getFiles()->toArray();

                if (isset($filesFromRequest) && key_exists($name, $filesFromRequest)) {
                    foreach ($filesFromRequest[$name] as $key => $tmpFile) {
                        $tmpName = $name . "[$key]";
                        $result[] = $this->helper->saveFileField($tmpName, $fileValidation);
                    }
                }
            } else {
                $result = $this->helper->saveFileField($name, $fileValidation);
            }
        }

        return $result;
    }

    /**
     * @param $filesArray
     * @param $name
     * @param $isMultiple
     * @return bool
     */
    private function isEmptyFiles($filesArray, $name, $isMultiple)
    {
        $hasName = array_key_exists($name, $filesArray);
        $error = $hasName ? UPLOAD_ERR_OK : UPLOAD_ERR_NO_FILE;

        if ($isMultiple && $hasName) {
            $file = array_pop($filesArray[$name]);
            $error = $file['error'] ?? 0;
        } elseif ($hasName) {
            $error = $filesArray[$name]['error'] ?? 0;
        }

        return $error == UPLOAD_ERR_NO_FILE;
    }

    /**
     * @param $field
     * @param $fieldType
     * @param $validation
     * @param $value
     * @param $fileValidation
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function validateField($field, $fieldType, $validation, $value, &$fileValidation)
    {
        foreach ($validation as $key => $item) {
            $name = $field['title'] ?? $field['label'];
            switch ($key) {
                case 'required':
                    if ($fieldType == 'file') {
                        $fileValidation[$key] = true;
                    }
                    if ($value === '' && ($fieldType != 'file')) {
                        if ($this->isHiddenField($field)) {
                            continue 2;
                        }

                        throw new LocalizedException(__('Please enter a %1.', $name));
                    }
                    break;
                case 'validation':
                    if ($item == 'validate-email' && !$this->isHiddenField($field)) {
                        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                        if (!\Zend_Validate::is($value, 'EmailAddress')) {
                            throw new LocalizedException(__('Please enter a valid email address.'));
                        }
                    }
                    break;
                case 'maxlength':
                    if (strlen($value) > (int)$item) {
                        throw new LocalizedException(
                            __('The length of %1 must be %2 characters or fewer.', $name, $item)
                        );
                    }
                    break;
                case 'allowed_extension':
                case 'max_file_size':
                    $fileValidation[$key] = $item;
                    break;
            }
        }
    }

    private function getRow($field, $type)
    {
        return isset($field[$type]) ? $field[$type] : null;
    }

    /**
     * field hidden by dependency
     *
     * @param array $field
     *
     * @return bool
     */
    private function isHiddenField($field)
    {
        $isHidden = false;
        if (isset($field['dependency']) && $field['dependency']) {
            foreach ($field['dependency'] as $dependency) {
                if (isset($dependency['field']) && isset($dependency[self::VALUE])) {
                    if (isset($this->params[$dependency['field']])) {
                        $isHidden = $this->params[$dependency['field']] != $dependency[self::VALUE];
                    } else {
                        $isHidden = true;
                    }
                }
            }
        }
        if (!$isHidden) {
            $emailField = $this->formProvider->getById(
                (int)$this->params['form_id']
            )->getEmailField();
            if ($emailField == $field['name'] && $this->helper->getCurrentCustomerId()) {
                $isHidden = true;
            }
        }

        return $isHidden;
    }
}
