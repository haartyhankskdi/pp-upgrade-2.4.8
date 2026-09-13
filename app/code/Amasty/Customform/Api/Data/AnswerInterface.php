<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Api\Data;

/**
 * @api
 */
interface AnswerInterface
{
    public const ANSWER_ID = 'answer_id';
    public const FORM_ID = 'form_id';
    public const STORE_ID = 'store_id';
    public const CREATED_AT = 'created_at';
    public const IP = 'ip';
    public const CUSTOMER_ID = 'customer_id';
    public const RESPONSE_JSON = 'response_json';
    public const ADMIN_RESPONSE_EMAIL = 'admin_response_email';
    public const ADMIN_RESPONSE_MESSAGE = 'admin_response_message';
    public const ADMIN_RESPONSE_STATUS = 'admin_response_status';
    public const REFERER_URL = 'referer_url';
    public const UPDATED_AT = 'updated_at';
    public const FORM_NAME = 'form_name';
    public const FORM_CODE = 'form_code';

    /**
     * @return int Answer id.
     */
    public function getAnswerId();

    /**
     * @param int $answerId
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setAnswerId($answerId);

    /**
     * @return int Form Id
     */
    public function getFormId();

    /**
     * @param int $formId
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setFormId($formId);

    /**
     * @return string
     */
    public function getStoreId();

    /**
     * @param string $storeId
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setStoreId($storeId);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getIp();

    /**
     * @param string $ip
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setIp($ip);

    /**
     * @return string
     */
    public function getResponseJson();

    /**
     * @param string $json
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setResponseJson($json);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $customerId
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setCustomerId($customerId);

    /**
     * @return string|null Admin response email. Otherwise, null.
     */
    public function getAdminResponseEmail();

    /**
     * @param string $responseEmail
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setAdminResponseEmail($responseEmail);

    /**
     * @return string|null Response message. Otherwise, null.
     */
    public function getResponseMessage();

    /**
     * @param string $responseMessage
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setResponseMessage($responseMessage);

    /**
     * @return string
     */
    public function getRecipientEmail();

    /**
     * @return string
     */
    public function getCustomerName();

    /**
     * @return string|null Response Status. Otherwise, null.
     */
    public function getAdminResponseStatus();

    /**
     * @param string $responseStatus
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setAdminResponseStatus($responseStatus);

    /**
     * @return string|null Referer Url. Otherwise, null.
     */
    public function getRefererUrl();

    /**
     * @param string $url
     *
     * @return \Amasty\Customform\Api\Data\AnswerInterface
     */
    public function setRefererUrl($url);

    /**
     * @param string|null $updatedAt
     *
     * @return void
     */
    public function setUpdatedAt(?string $updatedAt): void;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * @return string|null
     */
    public function getFormName(): ?string;

    /**
     * @param string|null $formName
     *
     * @return void
     */
    public function setFormName(?string $formName): void;

    /**
     * @return string|null
     */
    public function getFormCode(): ?string;

    /**
     * @param string|null $formCode
     *
     * @return void
     */
    public function setFormCode(?string $formCode): void;
}
