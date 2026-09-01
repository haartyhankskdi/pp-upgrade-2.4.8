<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Utilities;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class GetLocalDate
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

    public function execute(string $date, int $hour = 0, int $minute = 0, int $second = 0): string
    {
        $skipTimeZoneConversion = $this->scopeConfig->getValue(
            'config/skipTimeZoneConversion',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $date = $this->dateTimeFactory->create($date);
        $date->setTime($hour, $minute, $second);
        $timestampWithReversedOffset = $date->getTimestamp();

        if ($skipTimeZoneConversion) {
            $date->setTimezone(new \DateTimeZone('UTC'));
        } else {
            $timezoneDate = $this->timeZone->date(clone $date);
            $timestampWithReversedOffset -= $timezoneDate->getOffset();
        }

        return $date->setTimestamp($timestampWithReversedOffset)
            ->format('Y-m-d H:i:s');
    }
}
