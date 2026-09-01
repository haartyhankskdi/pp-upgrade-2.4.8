<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Utilities;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class GetTimeZoneOffset
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timeZone,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->timeZone = $timeZone;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    public function execute(): string
    {
        $skipTimeZoneConversion = $this->scopeConfig->getValue(
            'config/skipTimeZoneConversion',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $date = $skipTimeZoneConversion
            ? $this->dateTimeFactory->create()->setTimezone(new \DateTimeZone('UTC'))
            : $this->timeZone->date();

        return $date->format('P');
    }
}
