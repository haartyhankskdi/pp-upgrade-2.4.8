<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Card\Source;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magenest\SagePay\Model\Card
     */
    protected $_card;

    /**
     * Constructor
     *
     * @param \Magenest\SagePay\Model\Card $card
     */
    public function __construct(\Magenest\SagePay\Model\Card $card)
    {
        $this->_card = $card;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->_card->getAvailableStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }

        return $options;
    }
}
