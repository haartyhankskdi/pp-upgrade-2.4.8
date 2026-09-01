<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Ui\DataProvider\Popup\Modifier;

use Magezon\UiBuilder\Data\Form\Element\Factory;
use Magezon\UiBuilder\Data\Form\Element\CollectionFactory;

class AbstractModifier implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var Factory
     */
    protected $_factoryElement;

    /**
     * @var CollectionFactory
     */
    protected $_factoryCollection;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $_elements;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param Factory                     $factoryElement
     * @param CollectionFactory           $factoryCollection
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        \Magento\Framework\Registry $registry
    ) {
        $this->_factoryElement    = $factoryElement;
        $this->_factoryCollection = $factoryCollection;
        $this->registry           = $registry;
    }

    /**
     * Get current popup
     *
     * @return \Magezon\PopupBuilder\Model\Popup
     */
    public function getCurrentPopup()
    {
        return $this->registry->registry('current_popup');
    }

    /**
     * Get elements collection
     *
     * @return CollectionFactory
     */
    public function getElements()
    {
        if (empty($this->_elements)) {
            $this->_elements = $this->_factoryCollection->create();
        }

        return $this->_elements;
    }

    /**
     * @param $elementId
     * @param $type
     * @param array $config
     * @return \Magezon\UiBuilder\Data\Form\Element\AbstractElement|mixed
     */
    public function addChildren($elementId, $type, $config = [])
    {
        if (isset($this->_types[$type])) {
            $type = $this->_types[$type];
        }

        if (isset($config['required']) && $config['required']) {
            $validation                   = isset($config['validation']) ? $config['validation'] : [];
            $validation['required-entry'] = true;
            $config['validation']         = $validation;
        }

        $element = $this->_factoryElement->create($type, ['data' => ['config' => $config]]);
        $element->setId($elementId);
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $elementId
     * @param array $config
     * @return \Magezon\UiBuilder\Data\Form\Element\AbstractElement|mixed
     */
    public function addFieldset($elementId, $config = [])
    {
        $element = $this->_factoryElement->create('fieldset', ['data' => ['config' => $config]]);
        $element->setId($elementId);
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $elementId
     * @param array $config
     * @return \Magezon\UiBuilder\Data\Form\Element\AbstractElement|mixed
     */
    public function addContainer($elementId, $config = [])
    {
        $element = $this->_factoryElement->create('container', ['data' => ['config' => $config]]);
        $element->setId($elementId);
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $elementId
     * @param array $config
     * @return \Magezon\UiBuilder\Data\Form\Element\AbstractElement|mixed
     */
    public function addContainerGroup($elementId, $config = [])
    {
        $element = $this->_factoryElement->create('containerGroup', ['data' => ['config' => $config]]);
        $element->setId($elementId);
        $this->addElement($element);
        return $element;
    }

    /**
     * @param $element
     * @return $this
     */
    public function addElement($element)
    {
        $element->setForm($this);
        $this->getElements()->add($element);
        return $this;
    }

    /**
     * @param null $elements
     * @return array
     */
    public function getChildren($elements = null)
    {
        if (!$elements) {
            $elements = $this->getElements();
        }
        $children = [];
        foreach ($elements as $_element) {
            $children[$_element->getId()] = $_element->getElementConfig();
            if ($_element->getElements()->count()) {
                $children[$_element->getId()]['children'] = $this->getChildren($_element->getElements());
            }
        }
        return $children;
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Retrieve array manager
     *
     * @return \Magento\Framework\Stdlib\ArrayManager
     */
    protected function getArrayManager()
    {
        if (null === $this->arrayManager) {
            $this->arrayManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\ArrayManager::class
            );
        }
        return $this->arrayManager;
    }
}
