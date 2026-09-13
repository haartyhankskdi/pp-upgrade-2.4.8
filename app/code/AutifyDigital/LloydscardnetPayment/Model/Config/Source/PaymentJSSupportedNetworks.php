<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentJSSupportedNetworks implements OptionSourceInterface
{
    /**
     * Returns array of supported card networks
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'american-express', 'label' => __('American Express')],
            ['value' => 'discover', 'label' => __('Discover')],
            ['value' => 'electron', 'label' => __('Electron')],
            ['value' => 'maestro', 'label' => __('Maestro')],
            ['value' => 'mastercard', 'label' => __('Master Card')],
            ['value' => 'elo', 'label' => __('Elo')],
            ['value' => 'diners-club', 'label' => __('Diners Club')],
            ['value' => 'jcb', 'label' => __('JCB')],
            ['value' => 'mir', 'label' => __('Mir')],
            ['value' => 'unionpay', 'label' => __('Unionpay')],
            ['value' => 'visa', 'label' => __('Visa')]
        ];
    }
}
