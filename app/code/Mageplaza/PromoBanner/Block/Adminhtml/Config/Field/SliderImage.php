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
 * @package     Mageplaza_PromoBanner
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Block\Adminhtml\Config\Field;

use Exception;
use Magento\Backend\Block\Template;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Mageplaza\PromoBanner\Helper\Image as HelperImage;
use RuntimeException;

/**
 * Class SliderImage
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Config\Field
 */
class SliderImage extends AbstractFieldArray
{
    /**
     * {@inheritDoc}
     */
    protected $_template = 'Mageplaza_PromoBanner::form/element/gallery.phtml';

    /**
     * @var HelperImage
     */
    protected $helperImage;

    /**
     * SliderImage constructor.
     *
     * @param Template\Context $context
     * @param HelperImage $helperImage
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        HelperImage $helperImage,
        array $data = []
    ) {
        $this->helperImage = $helperImage;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->getElement()->getValue();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareToRender()
    {
        $this->addColumn('image', ['label' => __('Image'), 'type' => 'file', 'class' => 'required-entry input-file']);
        $this->addColumn('url', [
            'label' => __('Direct URL'),
            'type'  => 'text',
            'size'  => 40,
            'class' => 'validate-url validate-no-html-tags input-text'
        ]);
        $this->addColumn('sort_order', [
            'label' => __('Sort Order'),
            'type'  => 'text',
            'size'  => 5,
            'class' => 'required-entry validate-digits'
        ]);

        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add New Image');
    }

    /**
     * @param string $name
     * @param array $params
     */
    public function addColumn($name, $params)
    {
        $this->_columns[$name] = [
            'label'    => $this->_getParam($params, 'label', 'Column'),
            'size'     => $this->_getParam($params, 'size', false),
            'style'    => $this->_getParam($params, 'style'),
            'class'    => $this->_getParam($params, 'class'),
            'renderer' => false,
        ];
        if (!empty($params['type'])) {
            $this->_columns[$name]['type'] = $params['type'];
        }
    }

    /**
     * @param string $columnName
     *
     * @return string
     * @throws Exception
     */
    public function renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new RuntimeException('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];

        if ($column['type'] === 'file') {
            return '<input type="file" id="' .
                $this->_getCellInputElementId('<%- _id %>', $columnName) .
                '"' .
                ' name="slider_images<%- _id %>_image" 
                value="<%- ' .
                $columnName .
                ' %>" ' .
                ($column['size'] ? 'size="' .
                    $column['size'] .
                    '"' : '') .
                ' class="' .
                (isset(
                    $column['class']
                ) ? $column['class'] : 'input-text') . '"' . (isset(
                    $column['style']
                ) ? ' style="' . $column['style'] . '"' : '') . '/>';
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getMediaPath($value)
    {
        $url = '';
        if ($value) {
            $url = $this->helperImage->getImageSrc($value);
        }

        return $url;
    }
}
