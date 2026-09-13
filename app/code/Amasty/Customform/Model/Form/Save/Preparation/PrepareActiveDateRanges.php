<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Save\Preparation;

use Amasty\Customform\Api\Data\FormInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class PrepareActiveDateRanges implements PreparationInterface
{
    public const VISIBLE_VALUE = true;
    public const INVISIBLE_VALUE = false;
    public const RANGES_SECTION = 'activate_ranges';

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    public function prepare(array $formData): array
    {
        if (empty($formData[self::RANGES_SECTION])) {
            $formData[FormInterface::IS_VISIBLE] = self::VISIBLE_VALUE;
        } else {
            $dateFrom = empty($formData[self::RANGES_SECTION][FormInterface::SCHEDULED_FROM])
                ? null
                : $formData[self::RANGES_SECTION][FormInterface::SCHEDULED_FROM];
            $dateTo = empty($formData[self::RANGES_SECTION][FormInterface::SCHEDULED_TO])
                ? null
                : $formData[self::RANGES_SECTION][FormInterface::SCHEDULED_TO];
            $formData = array_merge(
                $formData,
                [
                    FormInterface::SCHEDULED_FROM => $dateFrom,
                    FormInterface::SCHEDULED_TO => $dateTo,
                    FormInterface::IS_VISIBLE => $this->isNowInDateRange($dateFrom, $dateTo)
                ]
            );
            unset($formData[self::RANGES_SECTION]);
        }

        return $formData;
    }

    private function isNowInDateRange(?string $dateFrom, ?string $dateTo): bool
    {
        $now = $this->timezone->date();
        $isNowInRange = true;

        if ($dateFrom) {
            $dateTimeFrom = $this->timezone->date($dateFrom);
            $isNowInRange = $isNowInRange && $now >= $dateTimeFrom;
        }

        if ($dateTo) {
            $dateTimeTo = $this->timezone->date($dateTo);
            $isNowInRange = $isNowInRange && $now < $dateTimeTo;
        }

        return $isNowInRange;
    }
}
