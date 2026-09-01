<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Utilities;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagFactory;
use Magento\Framework\Flag;

class GetDefaultToDate
{
    public const DATE_TO_FLAG = 'amasty_reports_to_date';

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
            $date = $this->getFlag(self::DATE_TO_FLAG)->loadSelf()->getFlagData() ?: time();
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
