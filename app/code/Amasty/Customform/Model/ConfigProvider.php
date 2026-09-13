<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model;

use Amasty\Base\Model\ConfigProviderAbstract;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider extends ConfigProviderAbstract
{
    public const PATH_PREFIX = 'amasty_customform/';
    public const DEFAULT_DATEFORMAT = 'mm/dd/yy';

    /**
     * @var string
     */
    protected $pathPrefix = self::PATH_PREFIX;

    public const XML_PATH_DATE_FORMAT = 'advanced/date_format';
    public const XML_PATH_EMAIL_SENDER = 'email/sender_email_identity';
    public const XML_PATH_EMAIL_RECIPIENTS = 'email/recipient_email';
    public const XML_PATH_GOOGLE_KEY = 'advanced/google_key';
    public const XML_PATH_GDPR_TEXT = 'gdpr/text';
    public const XML_PATH_FILE_LINK_LIFETIME = 'advanced/file_link_lifetime';

    public function getModuleConfig(string $path, ?string $scopeCode = null, ?int $scopeId = null): ?string
    {
        $scopeCode = $scopeCode ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        return $this->getValue($path, $scopeId, $scopeCode);
    }

    public function getDateFormat(): string
    {
        return (string) $this->getModuleConfig(self::XML_PATH_DATE_FORMAT) ?: self::DEFAULT_DATEFORMAT;
    }

    public function getEmailSender(): string
    {
        return (string) $this->getModuleConfig(self::XML_PATH_EMAIL_SENDER);
    }

    public function getRecipientEmails(): array
    {
        $config = (string) $this->getModuleConfig(self::XML_PATH_EMAIL_RECIPIENTS);
        $emails = array_map('trim', explode(',', $config));

        return array_filter($emails, function (string $email): bool {
            return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }

    public function getGoogleKey(): string
    {
        return (string) $this->getModuleConfig(self::XML_PATH_GOOGLE_KEY);
    }

    public function getGdprText(?int $storeId = null): string
    {
        return (string) $this->getModuleConfig(
            self::XML_PATH_GDPR_TEXT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getFileLinkLifetime(): ?int
    {
        $value = $this->getModuleConfig(self::XML_PATH_FILE_LINK_LIFETIME);

        return $value === null ? $value : (int) $value;
    }
}
