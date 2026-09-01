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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Block\Adminhtml\Ticket\Edit\Tab;

class Followup extends \Magento\Backend\Block\Widget\Form
{
    /**
     * @var \Mirasvit\Helpdesk\Helper\Html
     */
    private $helpdeskHtml;
    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Priority\CollectionFactory
     */
    private $priorityCollectionFactory;
    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Status\CollectionFactory
     */
    private $statusCollectionFactory;
    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;
    /**
     * @var \Mirasvit\Helpdesk\Model\Config\Source\Followupperiod
     */
    private $configSourceFollowupperiod;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    private $formFactory;
    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    private $context;

    /**
     * Followup constructor.
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Status\CollectionFactory $statusCollectionFactory
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Priority\CollectionFactory $priorityCollectionFactory
     * @param \Mirasvit\Helpdesk\Model\Config\Source\Followupperiod $configSourceFollowupperiod
     * @param \Mirasvit\Helpdesk\Helper\Html $helpdeskHtml
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\ResourceModel\Status\CollectionFactory $statusCollectionFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Priority\CollectionFactory $priorityCollectionFactory,
        \Mirasvit\Helpdesk\Model\Config\Source\Followupperiod $configSourceFollowupperiod,
        \Mirasvit\Helpdesk\Helper\Html $helpdeskHtml,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->priorityCollectionFactory = $priorityCollectionFactory;
        $this->configSourceFollowupperiod = $configSourceFollowupperiod;
        $this->localeDate = $context->getLocaleDate();
        $this->helpdeskHtml = $helpdeskHtml;
        $this->formFactory = $formFactory;
        $this->registry = $registry;
        $this->auth = $auth;
        $this->context = $context;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $form = $this->formFactory->create();
        $this->setForm($form);
        /** @var \Mirasvit\Helpdesk\Model\Ticket $ticket */
        $ticket = $this->registry->registry('current_ticket');

        $fieldset = $form->addFieldset('followup_fieldset', ['legend' => __('Follow Up')]);
        if ($ticket->getId()) {
            $fieldset->addField('ticket_id', 'hidden', [
                'name'  => 'ticket_id',
                'value' => $ticket->getId(),
            ]);
        }
        $fieldset->addField('fp_period_unit', 'select', [
            'label'  => __('Period'),
            'name'   => 'fp_period_unit',
            'value'  => $ticket->getFpPeriodUnit(),
            'values' => $this->configSourceFollowupperiod->toOptionArray(),
        ]);
        $fieldset->addField('fp_period_value', 'text', [
            'label' => __( 'Period Value' ),
            'name'  => 'fp_period_value',
            'value' => $ticket->getFpPeriodValue() ? $ticket->getFpPeriodValue() : '',
            'after_element_js' => '
                <script>
                require(["jquery",], function($) {
                        $(document).ready(function() {
                            var unitEl = $(\'#fp_period_unit\');
                            
                            fpDisplayCalendar(unitEl);
                            
                            $(unitEl).change(function() {
                                fpDisplayCalendar(this);
                            });
                            
                            function fpDisplayCalendar(el) {
                                if ($(el).val() == \'custom\') {
                                    $(\'.admin__field.field.field-fp_execute_at\').show();
                                    $(\'.admin__field.field.field-fp_period_value\').hide();
                                } else {
                                    $(\'.admin__field.field.field-fp_execute_at\').hide();
                                    $(\'.admin__field.field.field-fp_period_value\').show();
                                }
                            }
                        });
                    }
                );
                </script>',
        ]);
        $fieldset->addField('fp_execute_at', 'date', [
            'label'        => __( 'Execute At' ),
            'name'         => 'fp_execute_at',
            'value'        => $ticket->getFpExecuteAt(),
            'image'        => $this->getSkinUrl('images/grid-cal.gif'),
            'date_format'  => $this->localeDate->getDateFormat(\IntlDateFormatter::SHORT),
            'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
        ]);
        $fieldset->addField('fp_is_remind_hidden', 'hidden', [
            'name'  => 'fp_is_remind',
            'value' => 0,
        ]);
        $fieldset->addField('fp_is_remind', 'checkbox', [
            'label'   => __('Send Reminder'),
            'name'    => 'fp_is_remind',
            'value'   => 1,
            'checked' => $ticket->getFpIsRemind(),
        ]);
        $email = $ticket->getFpRemindEmail();
        if (!$ticket->getFpIsRemind()) {
            $email = $this->auth->getUser()->getEmail();
        }
        $fieldset->addField('fp_remind_email', 'text', [
            'label' => '',
            'name'  => 'fp_remind_email',
            'value' => $email ? implode(', ', explode(',', $email)) : '',
            'note'  => 'Emails to send reminder (comma-separated).',
        ]);
        $fieldset->addField('fp_status_id', 'select', [
            'label'  => __('Set Status To'),
            'name'   => 'fp_status_id',
            'value'  => $ticket->getFpStatusId(),
            'values' => $this->statusCollectionFactory->create()->toOptionArray(true),
        ]);
        $fieldset->addField('fp_priority_id', 'select', [
            'label'  => __('Set Priority To'),
            'name'   => 'fp_priority_id',
            'value'  => $ticket->getFpPriorityId(),
            'values' => $this->priorityCollectionFactory->create()->toOptionArray(true),
        ]);
        $fieldset->addField('fp_owner', 'select', [
            'label'  => __('Set Owner To'),
            'name'   => 'fp_owner',
            'value'  => $ticket->getFpDepartmentId() . '_' . $ticket->getFpUserId(),
            'values' => $this->helpdeskHtml->getAdminOwnerOptionArray(true),
        ]);

        return parent::_prepareForm();
    }
}
