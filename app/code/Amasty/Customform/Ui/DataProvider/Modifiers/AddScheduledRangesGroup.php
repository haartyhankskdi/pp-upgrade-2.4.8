<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Ui\DataProvider\Modifiers;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\Form\Save\Preparation\PrepareActiveDateRanges;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

class AddScheduledRangesGroup implements ModifierInterface
{
    public function modifyData(array $data): array
    {
        $data[PrepareActiveDateRanges::RANGES_SECTION] = [
            FormInterface::SCHEDULED_FROM => $data[FormInterface::SCHEDULED_FROM] ?? null,
            FormInterface::SCHEDULED_TO => $data[FormInterface::SCHEDULED_TO] ?? null
        ];

        return $data;
    }

    public function modifyMeta(array $meta): array
    {
        return $meta;
    }
}
