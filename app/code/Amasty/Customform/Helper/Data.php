<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

namespace Amasty\Customform\Helper;

use Amasty\Base\Model\Serializer;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\Session as Session;
use Magento\Framework\Math\Random;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const MEDIA_PATH = 'amasty/amcustomform/';

    public const FILE_WAS_NOT_UPLOADED_CODE_ERROR = '666';

    public const REDIRECT_PREVIOUS_PAGE = '/';

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    private $sessionFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $ioFile;

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    private $fileUploaderFactory;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $backendUrl;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var \Amasty\Customform\ViewModel\Form\FormInit\AnswerModeFactory
     */
    private $answerModeFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Random
     */
    protected $random;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\LayoutInterface $layout,
        \Amasty\Customform\ViewModel\Form\FormInit\AnswerModeFactory $answerModeFactory,
        \Amasty\Base\Model\Serializer $serializer,
        Session $session,
        Random $random
    ) {
        parent::__construct($context);

        $this->remoteAddress = $context->getRemoteAddress();
        $this->sessionFactory = $sessionFactory;
        $this->customerRepository = $customerRepository;
        $this->filesystem = $filesystem;
        $this->ioFile = $ioFile;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->backendUrl = $backendUrl;
        $this->escaper = $escaper;
        $this->layout = $layout;
        $this->serializer = $serializer;
        $this->answerModeFactory = $answerModeFactory;
        $this->session = $session;
        $this->random = $random;
    }

    /**
     * @param $path
     * @param int $storeId
     * @return mixed
     */
    public function getModuleConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            'amasty_customform/' . $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param array|string $data
     * @param array $allowedTags
     *
     * @return array|string
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        if (is_array($data)) {
            $result = [];

            foreach ($data as $key => $item) {
                // save keys after escape array
                $result[$key] = $this->escapeHtml($item, $allowedTags);
            }
        } else {
            $data = $allowedTags ? $data : strip_tags((string)$data);
            $result = $this->escaper->escapeHtml($data, $allowedTags);
        }

        return $result;
    }

    public function renderForm(int $formId, ?bool $popup = null, ?string $buttonText = null)
    {
        $viewModel = $this->answerModeFactory->create([
            'formId' => (int) $formId,
            'popup' => $popup,
            'buttonText' => $buttonText
        ]);
        $html = $this->layout->createBlock(
            \Amasty\Customform\Block\Init::class,
            'amasty_customform_init' . $formId,
            [
                'data' => [
                    'view_model' => $viewModel,
                    'form_id' => $formId
                ]
            ]
        )->toHtml();

        return $html;
    }

    public function getSubmitUrl()
    {
        return $this->_getUrl('amasty_customform/form/submit');
    }

    public function setUniqueHash($value)
    {
        $this->session->start();
        $this->session->setUniqueHashKey($value);
    }
    public function getUniqueHash(){
        $this->session->start();
        return $this->session->getUniqueHashKey();
    }
    public function unsUniqueHash(){
        $this->session->start();
        return $this->session->unsUniqueHashKey();
    }

    public function setFormValue($value)
    {
        $this->session->start();
        $this->session->setFormSubmittedValue($value);
    }

    public function getFormValue()
    {
        $this->session->start();
        return $this->session->getFormSubmittedValue();
    }

    public function getQuestionnaireUniqueId(){
        $hashKeyArray = [];
        $hashKey = $this->random->getUniqueHash();
        // $this->session->setUniqueSingleHashKey($hashKey);
        if($this->getUniqueHash()){
            // $hashKeyArray = json_decode($this->getUniqueHash());
            array_push($hashKeyArray, $hashKey);
            $hashKeyJson = json_encode($hashKeyArray);
        } else {
            array_push($hashKeyArray, $hashKey);
            $hashKeyJson = json_encode($hashKeyArray);
        }
        $this->setUniqueHash($hashKeyJson);
        return $hashKey;
    }

    public function getCurrentIp()
    {
        // $hashKeyArray = [];
        // $hashKey = $this->random->getUniqueHash();
        // if($this->getUniqueHash()){
        //     $hashKeyArray = json_decode($this->getUniqueHash());
        //     array_push($hashKeyArray, $hashKey);
        //     $hashKeyJson = json_encode($hashKeyArray);
        // } else {
        //     array_push($hashKeyArray, $hashKey);
        //     $hashKeyJson = json_encode($hashKeyArray);
        // }
        // $this->setUniqueHash($hashKeyJson);
        // return $hashKey;
        return $this->remoteAddress->getRemoteAddress();
    }

    public function getCurrentCustomerId()
    {
        $customerSession = $this->sessionFactory->create();

        return $customerSession->getCustomerId();
    }

    public function encode($data)
    {
        return $this->serializer->serialize($data);
    }

    public function decode($data)
    {
        return $this->serializer->unserialize($data);
    }

    /**
     * @param $customerId
     * @param bool $asLink
     * @return array|\Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomerName($customerId, $asLink = false)
    {
        $customerName = __('Guest');

        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (\Exception $ex) {
            $customer = null;
        }

        if ($customer) {
            $link = $this->backendUrl->getUrl('customer/index/edit', ['id' => $customer->getId()]);
            $linkString = sprintf(
                '<a href="%s">%s</a>',
                $link,
                $customer->getFirstname() . ' ' . $customer->getLastname()
            );
            $customer = [
                'customer_link' => ($asLink ? $linkString : $link),
                'customer_name' => $customer->getFirstname() . ' ' . $customer->getLastname()
            ];
        } else {
            $customer = [
                'customer_name' => $customerName,
                'customer_link' => ''
            ];
        }

        return $customer;
    }

    public function getAnswerViewUrl($id)
    {
        return $this->backendUrl->getUrl(
            'amasty_customform/answer/edit',
            [
                'id' => $id,
                UrlInterface::SECRET_KEY_PARAM_NAME => $this->backendUrl->getSecretKey()
            ]
        );
    }

    /**
     * @param $name
     * @param $fileValidation
     * @return array
     * @throws LocalizedException
     */
    public function saveFileField($name, $fileValidation)
    {
        //upload images
        $path = $this->filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            self::MEDIA_PATH
        );
        $this->ioFile->checkAndCreateFolder($path);

        try {
            /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
            $uploader = $this->fileUploaderFactory->create(['fileId' => $name]);

            if (!$uploader->getFileExtension()) {
                throw new LocalizedException(
                    __('Can\'t save file without extensions name')
                );
            }

            if (array_key_exists('allowed_extension', $fileValidation)) {
                $uploader->setAllowedExtensions(
                    array_map(
                        function ($extension) {
                            return trim($extension);
                        },
                        explode(',', $fileValidation['allowed_extension'])
                    )
                );
            }

            if (array_key_exists('max_file_size', $fileValidation)) {
                if ($uploader->getFileSize() > (float)$fileValidation['max_file_size'] * 1024 * 1024) {
                    throw new LocalizedException(
                        __('Field exceeds the allowed file size(%1 mb).', $fileValidation['max_file_size'])
                    );
                }
            }

            $uploader->setAllowRenameFiles(true);
            $result = $uploader->save($path);
        } catch (\Exception $ex) {
            if ($ex->getCode() == self::FILE_WAS_NOT_UPLOADED_CODE_ERROR && !$fileValidation) {
                return $result['file'] = [];
            }

            if ($ex->getCode() == self::FILE_WAS_NOT_UPLOADED_CODE_ERROR
                && array_key_exists('max_file_size', $fileValidation)
            ) {
                throw new LocalizedException(
                    __('Field exceeds the allowed file size(%1 mb).', $fileValidation['max_file_size'])
                );
            }

            throw new LocalizedException(__($ex->getMessage()));
        }

        return $result['file'];
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isGDPREnabled($storeId = null)
    {
        return (bool)$this->getModuleConfig('gdpr/enabled', $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getGDPRText($storeId = null)
    {
        return $this->getModuleConfig('gdpr/text', $storeId);
    }

    /**
     * @return bool
     */
    public function isAutoReplyEnabled()
    {
        return (bool)$this->getModuleConfig('autoresponder/enabled');
    }

    /**
     * @return string
     */
    public function getAutoReplySender()
    {
        return $this->getModuleConfig('autoresponder/sender');
    }

    /**
     * @return string
     */
    public function getAutoReplyTemplate()
    {
        return $this->getModuleConfig('autoresponder/template');
    }

    /**
     * @return mixed
     */
    public function getGoogleKey()
    {
        return $this->getModuleConfig('advanced/google_key');
    }

    public function isCanRenderGoogleMapInPdf(): bool
    {
        return (bool) $this->getModuleConfig('advanced/is_render_google_map_in_pdf');
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        $dateFormat = $this->getModuleConfig('advanced/date_format') ?: 'mm/dd/yy';

        return $dateFormat;
    }

    /**
     * @return bool
     */
    public function isSendNotification()
    {
        return (bool) $this->getModuleConfig('email/enabled');
    }

    /**
     * @param $element
     *
     * @return array
     */
    public function generateValidation($element): array
    {
        $validation = [];
        $validationFields = ['required', 'validation', 'allowed_extension', 'max_file_size', 'maxlength'];

        foreach ($validationFields as $field) {
            if (array_key_exists($field, $element) && $element[$field]) {
                $validation[$field] = strip_tags($element[$field]);
            }
        }

        return $validation;
    }
}
