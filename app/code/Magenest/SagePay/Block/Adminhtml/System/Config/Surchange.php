<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Adminhtml\System\Config;

use Magenest\SagePay\Block\Adminhtml\System\Config\Field\CardType;
use Magenest\SagePay\Block\Adminhtml\System\Config\Field\SurchangeType;
use Magenest\SagePay\Block\Adminhtml\System\Config\Field\Value;

class Surchange extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{

    protected $cardTypeRenderer;

    /**
     * @var string
     */
    protected $_template = 'Magenest_SagePay::system/config/form/field/array.phtml';

    protected function getCardTypeRender()
    {
        return $this->getRenderer('card_type', CardType::class);
    }

    protected function getSurchangeTypeRender()
    {
        return $this->getRenderer('surchange_type', SurchangeType::class);
    }

    public function getValueRenderer()
    {
        return $this->getRenderer('value', Value::class);
    }

    public function getRenderer($name, $class)
    {
        if (!$this->getData($name)) {
            $renderer = $this->getLayout()->createBlock(
                $class,
                $name,
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->setData($name, $renderer);
        }
        return $this->getData($name);
    }
    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('payment_type', [
            'label' => __('Payment Type'),
            'renderer' => $this->getCardTypeRender()
        ]);
        $this->addColumn('surchange_type', [
            'label' => __('Surcharges Type'),
            'renderer' => $this->getSurchangeTypeRender()
        ]);
        $this->addColumn(
            'value',
            []
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object.
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];

        $customAttribute = $row->getData('payment_type');
        $key = 'option_' . $this->getCardTypeRender()->calcOptionHash($customAttribute);
        $options[$key] = 'selected="selected"';

        $customAttribute = $row->getData('surchange_type');
        $key = 'option_' . $this->getSurchangeTypeRender()->calcOptionHash($customAttribute);
        $options[$key] = 'selected="selected"';

        $row->setData('option_extra_attrs', $options);
    }
}
