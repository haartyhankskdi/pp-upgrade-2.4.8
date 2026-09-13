<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Block\Adminhtml\Data\Form\Element;

use Magento\Framework\Escaper;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\CollectionFactory;

class Creator extends \Magento\Framework\Data\Form\Element\Fieldset
{
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    private $layoutFactory;

    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        array $data
    ) {
        $this->layoutFactory = $layoutFactory;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->_renderer = $this->getFormRenderer();
    }

    /**
     * @return \Amasty\Customform\Block\Widget\Form\Renderer\Creator
     */
    protected function getFormRenderer()
    {
        $layout = $this->layoutFactory->create();

        return $layout->createBlock(
            \Amasty\Customform\Block\Widget\Form\Renderer\Creator::class,
            'amasty_customform_creator_fieldset'
        );
    }

    public function getHtmlId()
    {
        return 'creator';
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_escaper->escapeHtml($this->getData('name'));
    }
}
