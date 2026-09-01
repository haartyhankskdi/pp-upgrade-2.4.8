<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Source\Quote;

use Amasty\Reports\Model\Di\Wrapper;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var Wrapper
     */
    private $status;

    public function __construct(
        Wrapper $status
    ) {
        $this->status = $status;
    }

    public function toOptionArray()
    {
        $result = [];
        $visibleOptions = $this->getVisibleOnFrontStatuses();
        foreach ($this->status->toOptionArray() as $option) {
            if (in_array($option['value'], $visibleOptions)) {
                $result[] = $option;
            }
        }

        return $result;
    }

    public function getVisibleOnFrontStatuses(): array
    {
        return $this->status->getVisibleOnFrontStatuses();
    }

    public function getStatusLabel(int $status): string
    {
        $label = $this->status->getStatusLabel($status);

        return is_string($label) ? $label : $label->render();
    }
}
