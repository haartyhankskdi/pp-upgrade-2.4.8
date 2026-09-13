<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SupportedNetworks implements OptionSourceInterface
{
    /**
     * Returns array of supported card networks
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'amex', 'label' => __('American Express')],
            ['value' => 'discover', 'label' => __('Discover')],
            ['value' => 'electron', 'label' => __('Electron')],
            ['value' => 'maestro', 'label' => __('Maestro')],
            ['value' => 'masterCard', 'label' => __('Master Card')],
            ['value' => 'visa', 'label' => __('Visa')]
        ];
    }
}
