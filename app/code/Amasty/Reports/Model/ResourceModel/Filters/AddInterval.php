<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Filters;

use Amasty\Reports\Model\Utilities\TimeZoneExpressionModifier;
use Magento\Framework\Data\Collection\AbstractDb;

class AddInterval
{
    /**
     * @var RequestFiltersProvider
     */
    private $filtersProvider;

    /**
     * @var TimeZoneExpressionModifier
     */
    private $timeZoneExpressionModifier;

    public function __construct(
        RequestFiltersProvider $filtersProvider,
        TimeZoneExpressionModifier $timeZoneExpressionModifier
    ) {
        $this->filtersProvider = $filtersProvider;
        $this->timeZoneExpressionModifier = $timeZoneExpressionModifier;
    }

    public function execute(
        AbstractDb $collection,
        string $dateFiled = 'created_at',
        string $tablePrefix = 'main_table'
    ): void {
       
        $interval = $this->getIntervalType();
        $collection->setFlag('interval', $interval);
        
        $fieldExpression = $this->timeZoneExpressionModifier->execute($dateFiled);
        $completeFieldExpression = $this->timeZoneExpressionModifier->execute(
            sprintf("%s.%s", $tablePrefix, $dateFiled)
        );
        
        switch ($interval) {
            case 'year':
                $collection->getSelect()
                    ->columns([
                        'period' => "YEAR($fieldExpression)",
                    ])
                    ->group("YEAR($completeFieldExpression)");
                break;
            case 'month':
                $collection->getSelect()
                    ->columns([
                        'period' => "DATE_FORMAT($completeFieldExpression,'%Y-%m')",
                    ])
                    ->group("MONTH($completeFieldExpression)");
                break;
            case 'week':
                $collection->getSelect()
                    ->columns([
                        'period' => "CONCAT(ADDDATE(DATE($completeFieldExpression), "
                            . "INTERVAL 1-DAYOFWEEK($completeFieldExpression) DAY), "
                            . "' - ', ADDDATE(DATE($completeFieldExpression), "
                            . "INTERVAL 7-DAYOFWEEK($completeFieldExpression) DAY))",
                    ])
                    ->group("WEEK($completeFieldExpression)");
                break;
            case 'day':
            default:
                $collection->getSelect()
                    ->columns([
                        'period' => "DATE($completeFieldExpression)",
                    ])
                    ->group("DATE($completeFieldExpression)");
        }
    }
    
    private function getIntervalType(): string
    {
        $filters = $this->filtersProvider->execute();
        
        return $filters['interval'] ?? 'day';
    }
}
