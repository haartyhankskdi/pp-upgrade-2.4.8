<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Adminhtml\System\Config\Field;

class CardType extends \Magento\Framework\View\Element\Html\Select
{

    /**
     * Model Enabledisable
     *
     * @var \Magento\Config\Model\Config\Source\Enabledisable
     */
    protected $_enableDisable;

    /**
     * Activation constructor.
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Config\Model\Config\Source\Enabledisable $enableDisable $enableDisable
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Config\Model\Config\Source\Enabledisable $enableDisable,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_enableDisable = $enableDisable;
    }

    /**
     * @param string $value
     * @return \Magenest\AutoImport\Block\Adminhtml\Form\Field\Activation
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function getCardType()
    {
        return [
            [
                'value' => 'MC',
                'label' => 'MasterCard'
            ],
            [
                'value' => 'VISA',
                'label' => 'VISA Credit'
            ],
            [
                'value' => 'DELTA',
                'label' => 'VISA Debit'
            ],
            [
                'value' => 'AMEX',
                'label' => 'American Express'
            ],
            [
                'value' => 'DC',
                'label' => 'Diner\'s Club'
            ],
            [
                'value' => 'MAESTRO',
                'label' => 'Maestro'
            ],
//            [
//                'value' => 'MCDEBIT',
//                'label' => 'Maestro'
//            ],
            [
                'value' => 'UKE',
                'label' => 'VISA Electron'
            ]
        ];
    }
    /**
     * Parse to html.
     *
     * @return mixed
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $cardTypes = $this->getCardType();

            foreach ($cardTypes as $cardType) {
                $this->addOption($cardType['value'], $cardType['label']);
            }
        }

        return parent::_toHtml();
    }
}
