<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Block\Adminhtml\Form\Creator;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Data\Form\Element\Factory as FactoryElement;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Wrapper extends Template
{
    /**
     * @var FactoryElement
     */
    private $factoryElement;

    public function __construct(
        Context $context,
        FactoryElement $factoryElement,
        array $data = []
    ) {
        $this->factoryElement = $factoryElement;

        parent::__construct($context, $data);
    }

    public function _toHtml()
    {
        $element = $this->factoryElement->create(
            \Amasty\Customform\Block\Adminhtml\Data\Form\Element\Creator::class,
            [
                'data' => [
                    'name' => 'creator',
                    'label' => '',
                    'title' => __('Form Creator'),
                ]
            ]
        );
        $element->setId('creator')->setLegend(__('Form Creator'));

        return $element->getHtml();
    }
}
