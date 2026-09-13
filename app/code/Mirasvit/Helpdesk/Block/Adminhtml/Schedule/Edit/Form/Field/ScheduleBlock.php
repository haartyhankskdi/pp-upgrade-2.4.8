<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\Helpdesk\Block\Adminhtml\Schedule\Edit\Form\Field;

use Magento\Framework\Escaper;
use Magento\Framework\Data\Form\Element;

class ScheduleBlock extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    private $formFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * ScheduleBlock constructor.
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        $data = []
    ) {
        $this->formFactory = $formFactory;
        $this->registry    = $registry;

        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function toHtml()
    {
        /** @var \Mirasvit\Helpdesk\Model\Schedule $schedule */
        $schedule = $this->registry->registry('current_schedule');
        $fieldset = $this->formFactory->create()->addFieldset('edit_fieldset', ['legend' => __('General Information')]);
        $element = $fieldset->addField(
            'working_time',
            'Mirasvit\Helpdesk\Block\Adminhtml\Schedule\Edit\Form\Field\Schedule',
            [
                'label' => __('Working days/hours'),
                'name'  => 'working_time',
                'value' => $schedule->getWorkingHours(),
            ],
            'working_hours'
        );

        return $element->getHtml();
    }
}
