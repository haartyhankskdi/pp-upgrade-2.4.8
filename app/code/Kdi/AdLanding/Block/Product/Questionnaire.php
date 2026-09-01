<?php

declare(strict_types=1);

namespace Kdi\AdLanding\Block\Product;

use Kdi\AdLanding\Helper\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Questionnaire extends Template
{
    /**
     * @var Config
     */
    private Config $configHelper;

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $configHelper,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * Check if the module is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configHelper->isEnabled();
    }

    /**
     * Get new customer questionnaire.
     *
     * @return string|null
     */
    public function getNewCustomerQuestionnaire(): ?string
    {
        return $this->configHelper->getNewCustomerQuestionnaire();
    }

    /**
     * Get repeat customer questionnaire.
     *
     * @return string|null
     */
    public function getRepeatCustomerQuestionnaire(): ?string
    {
        return $this->configHelper->getRepeatCustomerQuestionnaire();
    }

    /**
     * Get config helper instance.
     *
     * @return Config
     */
    public function getConfigHelper(): Config
    {
        return $this->configHelper;
    }
}
