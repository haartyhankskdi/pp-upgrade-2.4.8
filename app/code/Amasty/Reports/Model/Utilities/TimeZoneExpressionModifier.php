<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Utilities;

class TimeZoneExpressionModifier
{
    /**
     * @var GetTimeZoneOffset
     */
    private $getTimeZoneOffset;

    public function __construct(GetTimeZoneOffset $getTimeZoneOffset)
    {
        $this->getTimeZoneOffset = $getTimeZoneOffset;
    }

    public function execute(string $fieldName): string
    {
        $offset = $this->getTimeZoneOffset->execute();
        return sprintf('CONVERT_TZ(%s, \'+00:00\', \'%s\')', $fieldName, $offset);
    }
}
