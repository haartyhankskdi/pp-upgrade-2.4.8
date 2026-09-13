<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @api
 */
interface FormInterface extends ExtensibleDataInterface
{
    public const FORM_ID = 'form_id';
    public const CODE = 'code';
    public const TITLE = 'title';
    public const SUCCESS_URL = 'success_url';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const CUSTOMER_GROUP = 'customer_group';
    public const STORE_ID = 'store_id';
    public const SEND_NOTIFICATION = 'send_notification';
    public const SEND_TO = 'send_to';
    public const EMAIL_TEMPLATE = 'email_template';
    public const SUBMIT_BUTTON = 'submit_button';
    public const SUCCESS_MESSAGE = 'success_message';
    public const FORM_JSON = 'form_json';
    public const EMAIL_FIELD = 'email_field';
    public const EMAIL_FIELD_HIDE = 'email_field_hide';
    public const POPUP_SHOW = 'popup_show';
    public const POPUP_BUTTON = 'popup_button';
    public const FORM_TITLE = 'form_title';
    public const SAVE_REFERER_URL = 'save_referer_url';
    public const AUTO_REPLY_TEMPLATE = 'auto_reply_template';
    public const AUTO_REPLY_ENABLE = 'auto_reply_enable';
    public const SURVEY_MODE_ENABLE = 'survey_mode_enable';
    public const DESIGN = 'design';
    public const SCHEDULED_FROM = 'scheduled_from';
    public const SCHEDULED_TO = 'scheduled_to';
    public const FORM_CONTAINS_SENSITIVE_DATA = 'form_contains_sensitive_data';
    public const IS_VISIBLE = 'is_visible';

    public const IS_SUBSCRIPTION_ENABLED = 'subscription_enabled';
    public const IS_SUBSCRIPTION_REQUIRED = 'subscription_required';
    public const SUBSCRIPTION_TEXT = 'subscription_text';

    public const ADMIN_RESOURCE_DELETE = 'Amasty_Customform::delete';

    /**
     * @return int
     */
    public function getFormId();

    /**
     * @param int $formId
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setFormId($formId);

    /**
     * @return string
     */
    public function getCode();

    /**
     * @param string $code
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setCode($code);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param string $title
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getSuccessUrl();

    /**
     * @param string $successUrl
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setSuccessUrl($successUrl);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $status
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setStatus($status);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getCustomerGroup();

    /**
     * @param string $customerGroup
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setCustomerGroup($customerGroup);

    /**
     * @return string
     */
    public function getStoreId();

    /**
     * @param string $storeId
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setStoreId($storeId);

    /**
     * @return int
     */
    public function getSendNotification();

    /**
     * @param int $sendNotification
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setSendNotification($sendNotification);

    /**
     * @return string
     */
    public function getSendTo();

    /**
     * @param string $sendTo
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setSendTo($sendTo);

    /**
     * @return string
     */
    public function getEmailTemplate();

    /**
     * @param string $emailTemplate
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setEmailTemplate($emailTemplate);

    /**
     * @return string
     */
    public function getSubmitButton();

    /**
     * @param string $submitButton
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setSubmitButton($submitButton);

    /**
     * @return string
     */
    public function getSuccessMessage();

    /**
     * @param string $successMessage
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setSuccessMessage($successMessage);

    /**
     * @return string
     */
    public function getOrigFormJson();

    /**
     * @return string
     */
    public function getFormJson();

    /**
     * @param string $json
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setFormJson($json);

    /**
     * @return bool
     */
    public function isHideEmailField();

    /**
     * @param bool $hide
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setEmailFieldHide($hide);

    /**
     * @return string
     */
    public function getEmailField();

    /**
     * @param string $emailField
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setEmailField($emailField);

    /**
     * @return bool
     */
    public function isPopupShow();

    /**
     * @param bool $popupShow
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setPopupShow($popupShow);

    /**
     * @return string
     */
    public function getPopupButton();

    /**
     * @param string $popupButton
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setPopupButton($popupButton);

    /**
     * @return string
     */
    public function getFormTitle();

    /**
     * @param string $json
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setFormTitle($json);

    /**
     * @return string
     */
    public function getAutoReplyTemplate();

    /**
     * @param string $template
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setAutoReplyTemplate($template);

    /**
     * @return bool
     */
    public function isAutoReplyEnabled();

    /**
     * @param bool $value
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setIsAutoReplyEnabled($value);

    /**
     * @return bool
     */
    public function isSurveyModeEnabled();

    /**
     * @param bool $value
     *
     * @return \Amasty\Customform\Api\Data\FormInterface
     */
    public function setIsSurveyModeEnabled($value);

    /**
     * @return bool
     */
    public function getFormContainsSensitiveData(): bool;

    /**
     * @param bool $isContainsSensitiveData
     *
     * @return void
     */
    public function setFormContainsSensitiveData(bool $isContainsSensitiveData): void;

    /**
     * @param string $scheduledFrom
     *
     * @return void
     */
    public function setScheduledFrom(string $scheduledFrom): void;

    /**
     * @return string|null
     */
    public function getScheduledFrom(): ?string;

    /**
     * @param string $scheduledTo
     *
     * @return void
     */
    public function setScheduledTo(string $scheduledTo): void;

    /**
     * @return string|null
     */
    public function getScheduledTo(): ?string;

    /**
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsVisible(): bool;

    /**
     * @param bool $isVisible
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function setIsVisible(bool $isVisible): void;

    /**
     * Extension for module CustomFormNewsletterSubscription.
     * @return bool|null null in case module CustomFormNewsletterSubscription not installed
     */
    public function isSubscriptionEnabled(): ?bool;

    /**
     * Extension for module CustomFormNewsletterSubscription.
     * @param bool $isSubscriptionEnabled
     * @return void
     */
    public function setSubscriptionEnabled(bool $isSubscriptionEnabled): void;

    /**
     * Extension for module CustomFormNewsletterSubscription.
     * @return bool|null null in case module CustomFormNewsletterSubscription not installed
     */
    public function isSubscriptionRequired(): ?bool;

    /**
     * Extension for module CustomFormNewsletterSubscription.
     * @param bool $isSubscriptionRequired
     * @return void
     */
    public function setSubscriptionRequired(bool $isSubscriptionRequired): void;

    /**
     * Extension for module CustomFormNewsletterSubscription.
     * @return string|null null in case module CustomFormNewsletterSubscription not installed
     */
    public function getSubscriptionText(): ?string;

    /**
     * Extension for module CustomFormNewsletterSubscription.
     * @param string $subscriptionText
     * @return void
     */
    public function setSubscriptionText(string $subscriptionText): void;

    /**
     * @return \Amasty\Customform\Api\Data\FormExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Amasty\Customform\Api\Data\FormExtensionInterface;

    /**
     * @param \Amasty\Customform\Api\Data\FormExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        FormExtensionInterface $extensionAttributes
    ): void;
}
