<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Utilities;

use Amasty\Reports\Model\ResourceModel\Filters\RequestFiltersProvider;
use Magento\Framework\Stdlib\DateTime\DateTime;

class CreateUniqueHash
{
    public const INTERVAL_DAY = 'day';
    public const TYPE_OVERVIEW = 'overview';

    /**
     * @var RequestFiltersProvider
     */
    private $filtersProvider;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var GetDefaultFromDate
     */
    private $getDefaultFromDate;

    public function __construct(
        RequestFiltersProvider $filtersProvider,
        DateTime $dateTime,
        GetDefaultFromDate $getDefaultFromDate
    ) {
        $this->filtersProvider = $filtersProvider;
        $this->dateTime = $dateTime;
        $this->getDefaultFromDate = $getDefaultFromDate;
    }

    public function execute(): string
    {
        $filters = $this->filtersProvider->execute();
        $from = $filters['from'] ?? $this->dateTime->gmtDate('Y-m-d', $this->getDefaultFromDate->execute());
        $to = $filters['to'] ?? false;
        $store = $filters['store'] ?? false;
        $interval = $filters['interval'] ?? self::INTERVAL_DAY;
        $group = $filters['type'] ?? self::TYPE_OVERVIEW;

        return sha1($from . $to . $store . $interval . $group);
    }
}
