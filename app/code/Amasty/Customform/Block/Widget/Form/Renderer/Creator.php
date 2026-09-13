<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Block\Widget\Form\Renderer;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\FormRegistry;
use Magento\Backend\Block\Template\Context;
use Amasty\Customform\Model\Config\Source\DateFormat;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfigProvider;

class Creator extends \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
{
    protected $_template = 'Amasty_Customform::widget/form/renderer/fieldset.phtml';

    private $elementTypeConnection = [
      'textinput'   => 'input',
      'textarea'    => 'input',
      'number'      => 'input',
      'googlemap'   => 'input',
      'date'        => 'select',
      'time'        => 'select',
      'datetime'    => 'select',
      'file'        => 'select',
      'dropdown'    => 'options',
      'listbox'     => 'options',
      'checkbox'    => 'options',
      'checkboxtwo' => 'options',
      'radio'       => 'options',
      'radiotwo'    => 'options',
      'rating'      => 'other',
      'country'     => 'other',
      'address'     => 'other',
      'text'        => 'other',
      'hone'        => 'other',
      'htwo'        => 'other',
      'hthree'      => 'other',
      'wysiwyg'      => 'other'
    ];

    private $types = [
        'input'     => 'Input',
        'select'    => 'Select',
        'options'   => 'Options',
        'other'     => 'Advanced'
    ];

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Amasty\Customform\Helper\Messages
     */
    private $messagesHelper;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var \Amasty\Customform\Helper\Data
     */
    private $helper;

    /**
     * @var FormRegistry
     */
    private $formRegistry;

    /**
     * @var WysiwygConfigProvider
     */
    private $wysiwygConfigProvider;

    public function __construct(
        Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Amasty\Customform\Helper\Messages $messagesHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Amasty\Customform\Helper\Data $helper,
        FormRegistry $formRegistry,
        WysiwygConfigProvider $wysiwygConfigProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->objectManager = $objectManager;
        $this->messagesHelper = $messagesHelper;
        $this->jsonEncoder = $jsonEncoder;
        $this->helper = $helper;
        $this->formRegistry = $formRegistry;
        $this->wysiwygConfigProvider = $wysiwygConfigProvider;
    }

    /**
     * @return string
     */
    public function getFormJson(): string
    {
        $model = $this->getCurrentForm();
        $result = '[]';

        if ($model && $model->getFormJson()) {
            $result = $model->getFormJson();
        }

        return $result;
    }

    public function getFormTitles(): string
    {
        $model = $this->getCurrentForm();
        $result = '[]';

        if ($model && $model->getFormTitle()) {
            $result = $model->getFormTitle();
        }

        return $result;
    }

    private function getCurrentForm(): ?FormInterface
    {
        $model = $this->formRegistry->getCurrentForm()
            ?: $this->formRegistry->registry(FormRegistry::PERSISTED_DATA);

        return $model instanceof FormInterface ? $model : null;
    }

    /**
     * @return string[]
     */
    public function getElementTypes()
    {
        return $this->types;
    }

    /**
     * @param $type
     *
     * @return array
     */
    public function getElementsByType($type)
    {
        return array_keys($this->elementTypeConnection, $type);
    }

    /**
     * @return string
     */
    public function getFrmbFieldsJson()
    {
        $result = [];
        foreach ($this->elementTypeConnection as $key => $type) {
            $element = $this->_createElement($key);
            if ($element) {
                $data = $element->getElementData($key, $type);
                $result[] = $data;
            }
        }
        return $this->jsonEncoder->encode($result);
    }

    /**
     * @return string
     */
    public function getMessagesJson()
    {
        return $this->messagesHelper->getMessages();
    }

    /**
     * @return string
     */
    public function getTypeFieldsJson()
    {
        $result = [];
        foreach ($this->types as $key => $value) {
            $result[]= [
                'type' => $key,
                'title' => $value
            ];
        }
        return $this->jsonEncoder->encode($result);
    }

    /**
     * @param $name
     * @return bool|mixed
     */
    protected function _createElement($name)
    {
        $className = 'Amasty\Customform\Block\Widget\Form\Element\\' . ucfirst($name);
        if (class_exists($className)) {
            $element = $this->objectManager->create($className);
            return $element;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getGoogleKey()
    {
        return $this->helper->getGoogleKey();
    }

    /**
     * @return string
     */
    public function getInputFormat()
    {
        return DateFormat::FORMATS[$this->helper->getDateFormat()]['label'] ?? 'mm/dd/yy';
    }

    /**
     * @return string
     */
    public function getWysiwygConfigJson(): string
    {
        return $this->jsonEncoder->encode($this->wysiwygConfigProvider->getConfig());
    }
}
