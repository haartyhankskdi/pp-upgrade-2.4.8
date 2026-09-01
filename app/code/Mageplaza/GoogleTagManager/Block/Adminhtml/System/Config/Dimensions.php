<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;
use Mageplaza\GoogleTagManager\Helper\Data;

/**
 * Class Dimensions
 * @package Mageplaza\GoogleTagManager\Block\Adminhtml\System\Config
 */
class Dimensions extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Mageplaza_GoogleTagManager::system/config/dimensions.phtml';

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var
     */
    protected $_element;

    /**
     * Dimensions constructor.
     *
     * @param Context $context
     * @param Data $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helperData,
        array $data = []
    ) {
        $this->helperData = $helperData;

        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getOptionValues()
    {
        $values           = [];
        $customDimensions = $this->helperData->getDimensions();
        $options          = $customDimensions ? Data::jsonDecode($customDimensions) : [];

        if (!empty($options['option'])) {
            $values = $this->_prepareOptionValues($options);
        }

        return $values;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function _prepareOptionValues($options)
    {
        $values = [];

        foreach ($options['option']['value'] as $id => $option) {
            $bunch = $this->_prepareAttributeOptionValues(
                $id,
                $option
            );
            foreach ($bunch as $value) {
                $values[] = new DataObject($value);
            }
        }

        return $values;
    }

    /**
     * @param string $rowId
     * @param array $option
     *
     * @return array
     */
    protected function _prepareAttributeOptionValues($rowId, $option)
    {
        $value['id']    = $rowId;
        $value['name']  = $option['name'];
        $value['index'] = $option['index'];
        $value['value'] = $option['value'];

        return [$value];
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->_element = $element;

        return $this->_toHtml();
    }

    /**
     * Render HTML for element's label
     *
     * @param string $scopeLabel
     *
     * @return string
     */
    public function getLabelHtml($scopeLabel = '')
    {
        $scopeLabel = $scopeLabel ? ' data-config-scope="' . $scopeLabel . '"' : '';
        $label      = __('Custom Dimensions');

        return '<span' . $scopeLabel . '>' . $label . '</span>';
    }

    /**
     * @return mixed
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     *
     */
    public function getProductAndCustomerAttributes()
    {
        return $this->helperData->getProductAndCustomerAttributes();
    }
}
