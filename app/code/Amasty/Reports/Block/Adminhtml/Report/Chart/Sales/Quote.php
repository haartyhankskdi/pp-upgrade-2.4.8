<?php

declare(strict_types=1);

namespace Amasty\Reports\Block\Adminhtml\Report\Chart\Sales;

use Amasty\Reports\Block\Adminhtml\Report\Chart;

class Quote extends Chart
{
    public function getDefaultDisplayType(): string
    {
        return 'total';
    }
}
