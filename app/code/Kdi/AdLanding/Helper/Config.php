<?php

declare(strict_types=1);

namespace Kdi\AdLanding\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    public const XML_PATH_STATUS = 'ad_landing_page/general/status';
    public const XML_PATH_NEW_CUSTOMER_QUESTIONNAIRE = 'ad_landing_page/general/new_customer_questionnaire';
    public const XML_PATH_REPEAT_CUSTOMER_QUESTIONNAIRE = 'ad_landing_page/general/repeat_customer_questionnaire';

    /**
     * Config constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Get store configuration value.
     *
     * @param string $path
     * @param int|string|null $storeId
     * @return mixed
     */
    public function getConfigValue(string $path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check whether the module is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get new customer questionnaire ID/URL.
     *
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getNewCustomerQuestionnaire($storeId = null): ?string
    {
        return $this->getConfigValue(
            self::XML_PATH_NEW_CUSTOMER_QUESTIONNAIRE,
            $storeId
        );
    }

    /**
     * Get repeat customer questionnaire ID/URL.
     *
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getRepeatCustomerQuestionnaire($storeId = null): ?string
    {
        return $this->getConfigValue(
            self::XML_PATH_REPEAT_CUSTOMER_QUESTIONNAIRE,
            $storeId
        );
    }
}
