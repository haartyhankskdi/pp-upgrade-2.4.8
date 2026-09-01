<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Mail;

use Amasty\Customform\Helper\Data;
use Amasty\Customform\Model\Answer;
use Amasty\Customform\Model\Form;
use Amasty\Customform\Model\Template\TransportBuilderFactory;
use Amasty\Customform\ViewModel\Answser\Email\SubmittedFieldsRenderer;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Notification
{
    public const SYSTEM_CONFIG_VALUE = 2;

    /**
     * @var TransportBuilderFactory
     */
    private $transportBuilder;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var SubmittedFieldsRenderer
     */
    private $submittedFieldsRenderer;

    /**
     * @var EmailSender
     */
    private $emailSender;

    public function __construct(
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        TransportBuilderFactory $transportBuilderFactory,
        Data $helper,
        LoggerInterface $logger,
        EmailSender $emailSender,
        SubmittedFieldsRenderer $submittedFieldsRenderer,
        Escaper $escaper
    ) {
        $this->transportBuilder = $transportBuilderFactory->create();
        $this->helper = $helper;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->escaper = $escaper;
        $this->submittedFieldsRenderer = $submittedFieldsRenderer;
        $this->emailSender = $emailSender;
    }

    public function sendNotifications(Form $formModel, Answer $model)
    {
        $this->sendAdminNotification($formModel, $model);
        $this->sendAutoReply($formModel, $model);
    }

    /**
     * @param Form $formModel
     * @param Answer $model
     */
    private function sendAdminNotification(Form $formModel, Answer $model)
    {
        $emailTo = trim((string)$formModel->getSendTo());
        $globalEmailTo = $this->helper->getModuleConfig('email/recipient_email');
        if (!$emailTo && $globalEmailTo) {
            $emailTo = trim($globalEmailTo);
        }

        if ($emailTo && $this->isSendNotification($formModel)) {
            $sender = $this->helper->getModuleConfig('email/sender_email_identity');
            $template = $formModel->getEmailTemplate();
            if (!$template) {
                $template = $this->helper->getModuleConfig('email/template');
            }

            $model->setFormTitle($formModel->getTitle());
            $model->addData($this->getModelFields($model));
            $customerData = $this->helper->getCustomerName($model->getCustomerId());

            try {
                $store = $this->storeManager->getStore();
                $attachments = [];
                $data = [
                    'website_name' => $store->getWebsite()->getName(),
                    'group_name' => $store->getGroup()->getName(),
                    'store_name' => $store->getName(),
                    'response' => $model,
                    'link' => $this->helper->getAnswerViewUrl($model->getAnswerId()),
                    'submit_fields' => $this->submittedFieldsRenderer->render($model, $attachments),
                    'customer_name' => $customerData['customer_name'],
                    'customer_link' => $customerData['customer_link'],
                ];

                $this->emailSender->sendMail(
                    $emailTo,
                    $template,
                    $sender,
                    $data,
                    $attachments,
                    (int) $store->getId(),
                    $model->getRecipientEmail() ?: null
                );
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Unable to send the email.'));
            }
        }
    }

    /**
     * @param Form $formModel
     *
     * @return bool
     */
    protected function isSendNotification(Form $formModel)
    {
        $enabled = $formModel->getSendNotification();
        if ($enabled == self::SYSTEM_CONFIG_VALUE) {
            $enabled = $this->helper->isSendNotification();
        }

        if (!$this->helper->getModuleConfig('email/sender_email_identity')) {
            $this->logger->critical(
                __('Email was not sent. Please specify email sender in Amasty Custom Form module configuration.')
            );
            $enabled = false;
        }

        return $enabled;
    }

    /**
     * @param Answer $model
     *
     * @return array
     */
    protected function getModelFields(Answer $model)
    {
        $data = [];
        $fields = $model->getResponseJson() ? $this->helper->decode($model->getResponseJson()) : [];
        foreach ($fields as $key => $field) {
            $value = $this->getRow($field, 'value');
            if (is_array($value)) {
                $filteredFiles = array_filter($value);
                $value = implode(', ', $filteredFiles);
            }

            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * @param Form $formModel
     * @param Answer $model
     * @throws LocalizedException
     */
    private function sendAutoReply(Form $formModel, Answer $model)
    {
        if (!$this->isAutoReplyEnabled($formModel)) {
            return;
        }

        $emailTo = $model->getRecipientEmail();
        if ($emailTo) {
            $sender = $this->helper->getAutoReplySender();
            $template = $this->getAutoReplyTemplate($formModel);

            $model->setFormTitle($formModel->getTitle());
            $model->addData($this->getModelFields($model));
            $customerData = $this->helper->getCustomerName($model->getCustomerId());

            try {
                $store = $this->storeManager->getStore();
                $attachments = [];
                $data = [
                    'website_name' => $store->getWebsite()->getName(),
                    'group_name' => $store->getGroup()->getName(),
                    'store_name' => $store->getName(),
                    'response' => $model,
                    'customer_name' => $customerData['customer_name'] ?? '',
                    'form_name' => $formModel->getTitle(),
                    'submit_fields' => $this->submittedFieldsRenderer->render($model, $attachments)
                ];

                $this->emailSender->sendMail(
                    $emailTo,
                    $template,
                    $sender,
                    $data,
                    $attachments,
                    (int) $store->getId()
                );
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Unable to send the email.'));
            }
        }
    }

    /**
     * @param Form $formModel
     *
     * @return bool
     */
    protected function isAutoReplyEnabled(Form $formModel)
    {
        $enabled = $formModel->isAutoReplyEnabled();
        if ($enabled == self::SYSTEM_CONFIG_VALUE) {
            $enabled = $this->helper->isAutoReplyEnabled();
        }

        return $enabled;
    }

    /**
     * @param Form $formModel
     *
     * @return string
     */
    protected function getAutoReplyTemplate(Form $formModel)
    {
        $template = $formModel->getAutoReplyTemplate();
        if (!$template || $template == self::SYSTEM_CONFIG_VALUE) {
            $template = $this->helper->getAutoReplyTemplate();
        }

        return $template;
    }

    private function getRow($field, $type)
    {
        return isset($field[$type]) ? $this->escaper->escapeHtml($field[$type]) : null;
    }
}
