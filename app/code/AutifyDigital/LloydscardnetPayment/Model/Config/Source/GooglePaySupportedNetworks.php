<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Allowed Card types
 */
class GooglePaySupportedNetworks implements ArrayInterface
{
    /**
     * Return array of supported card networks
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'AMEX', 'label' => __('AMEX')],
            ['value' => 'INTERAC', 'label' => __('INTERAC')],
            ['value' => 'VISA', 'label' => __('VISA')],
            ['value' => 'MASTERCARD', 'label' => __('MasterCard')],
            ['value' => 'JCB', 'label' => __('JCB')],
            ['value' => 'DISCOVER', 'label' => __('Discover')],
        ];
    }

    /**
     * Get allowed networks as array
     *
     * @return array
     */
    public function getAllowedNetworks()
    {
        return ['VISA', 'MASTERCARD', 'JCB', 'DISCOVER'];
    }
}
