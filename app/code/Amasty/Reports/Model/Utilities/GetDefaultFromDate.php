<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Utilities;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagFactory;
use Magento\Framework\Flag;

class GetDefaultFromDate
{
    public const DATE_FROM_FLAG = 'amasty_reports_from_date';

    /**
     * @var FlagFactory
     */
    private $flagFactory;

    public function __construct(FlagFactory $flagFactory)
    {
        $this->flagFactory = $flagFactory;
    }

    /**
     * @return int
     */
    public function execute(): int
    {
        try {
            $date = $this->getFlag(self::DATE_FROM_FLAG)->loadSelf()->getFlagData() ?: strtotime('-7 day');
        } catch (LocalizedException $e) {
            $date = 0;
        }

        return (int) $date;
    }

    private function getFlag($code): Flag
    {
        return $this->flagFactory->create([
            'data' => [
                'flag_code' => $code
            ]
        ]);
    }
}
