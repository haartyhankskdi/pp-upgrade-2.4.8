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

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Url;
use Magento\Cms\Model\Wysiwyg\Config as CmsConfig;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Magento\Framework\File\Size as FileSize;
use Mirasvit\Helpdesk\Helper\Customer;
use Mirasvit\Helpdesk\Helper\Draft;
use Mirasvit\Helpdesk\Helper\Field;
use Mirasvit\Helpdesk\Helper\Html;
use Mirasvit\Helpdesk\Helper\Mage;
use Mirasvit\Helpdesk\Helper\Order;
use Mirasvit\Helpdesk\Model\Config;
use Mirasvit\Helpdesk\Model\PriorityFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Template\CollectionFactory;
use Mirasvit\Helpdesk\Model\StatusFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class General extends Form
{
    private $templates;

    private $helpdeskDraft;

    private $helpdeskHtml;

    private $priorityFactory;

    private $statusFactory;

    private $jsonEncoder;

    private $templateCollectionFactory;

    private $wysiwygConfig;

    private $config;

    private $formElementFactory;

    private $formFactory;

    private $helpdeskOrder;

    private $helpdeskCustomer;

    private $context;

    private $auth;

    private $registry;

    private $fileSize;

    private $helpdeskField;

    private $moduleManager;

    private $backendUrlManager;

    private $helpdeskMage;

    private $configWysiwyg;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        StatusFactory $statusFactory,
        PriorityFactory $priorityFactory,
        CollectionFactory $templateCollectionFactory,
        Config $config,
        Config\Wysiwyg $configWysiwyg,
        Field $helpdeskField,
        Mage $helpdeskMage,
        Draft $helpdeskDraft,
        FileSize $fileSize,
        Customer $helpdeskCustomer,
        Order $helpdeskOrder,
        Html $helpdeskHtml,
        Factory $formElementFactory,
        CmsConfig $wysiwygConfig,
        FormFactory $formFactory,
        Url $backendUrlManager,
        Manager $moduleManager,
        Registry $registry,
        Auth $auth,
        Context $context,
        Data $jsonEncoder,
        array $data = []
    ) {
        $this->statusFactory             = $statusFactory;
        $this->priorityFactory           = $priorityFactory;
        $this->templateCollectionFactory = $templateCollectionFactory;
        $this->config                    = $config;
        $this->configWysiwyg             = $configWysiwyg;
        $this->helpdeskField             = $helpdeskField;
        $this->helpdeskMage              = $helpdeskMage;
        $this->helpdeskDraft             = $helpdeskDraft;
        $this->fileSize                  = $fileSize;
        $this->helpdeskCustomer          = $helpdeskCustomer;
        $this->helpdeskOrder             = $helpdeskOrder;
        $this->helpdeskHtml              = $helpdeskHtml;
        $this->formElementFactory        = $formElementFactory;
        $this->wysiwygConfig             = $wysiwygConfig;
        $this->formFactory               = $formFactory;
        $this->backendUrlManager         = $backendUrlManager;
        $this->moduleManager             = $moduleManager;
        $this->registry                  = $registry;
        $this->auth                      = $auth;
        $this->context                   = $context;
        $this->jsonEncoder               = $jsonEncoder;

        parent::__construct($context, $data);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $messages = $this->getLayout()->createBlock('\Mirasvit\Helpdesk\Block\Adminhtml\Ticket\Edit\Tab\Messages');
        $this->setChild('helpdesk_messages', $messages);
        $this->setTemplate('ticket/edit/tab/general.phtml');

        return parent::_prepareLayout();
    }

    /**
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerSummaryHtml()
    {
        return $this->getLayout()->createBlock(
            'Mirasvit\Helpdesk\Block\Adminhtml\Ticket\Edit\Tab\General\CustomerSummary'
        )
            ->setTemplate('Mirasvit_Helpdesk::ticket/edit/tab/general/customer_summary.phtml')
            ->toHtml();
    }

    /**
     * @return \Magento\Framework\Data\Form\Element\Collection|\Magento\Framework\Data\Form\Element\AbstractElement[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomFields()
    {
        $form = $this->formFactory->create();
        $this->setForm($form);

        $ticket = $this->getTicket();

        $collection = $this->helpdeskField->getStaffCollection();

        if ($ticket->getStoreId()) {
            $collection->addStoreFilter($ticket->getStoreId());
        }

        if (!$collection->count()) {
            return [];
        }

        foreach ($collection as $field) {
            if ($field->getType() == 'checkbox') {
                $form->addField($field->getCode() . '1', 'hidden', ['name' => $field->getCode(), 'value' => 0]);
            }
            $params = $this->helpdeskField->getInputParams($field, true, $ticket);
            $form->addField(
                $field->getCode(),
                $field->getType(),
                $params
            );
        }

        return $form->getElements();
    }

    /**
     * @return Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getMessagesHtml()
    {
        return $this->getLayout()->createBlock('\Mirasvit\Helpdesk\Block\Adminhtml\Ticket\Edit\Tab\Messages')->toHtml();
    }

    /**
     * @return object
     */
    public function getTicket()
    {
        return $this->registry->registry('current_ticket');
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getNoticeMessage()
    {
        $ticket = $this->getTicket();

        if (!$ticket->getId()) {
            return '';
        }

        $userId = $this->auth->getUser()->getUserId();

        return $this->helpdeskDraft->getNoticeMessage($ticket->getId(), $userId);
    }

    /**
     * @return \Magento\Framework\UrlInterface
     */
    public function getUrlManager()
    {
        return $this->context->getUrlBuilder();
    }

    /**
     * @return string
     */
    public function getCustomerSummaryConfigJson()
    {
        $ticket           = $this->getTicket();
        $customersOptions = [];
        $ordersOptions    = [['name' => __('Unassigned'), 'id' => 0]];

        if ($ticket->getCustomerId() || $ticket->getQuoteAddressId()) {
            $customers = $this->helpdeskCustomer->getCustomerArray(
                false,
                $ticket->getCustomerId(),
                $ticket->getQuoteAddressId()
            );
            $email     = false;

            foreach ($customers as $value) {
                $customersOptions[] = ['name' => $value['name'], 'id' => $value['id']];
                $email              = $value['email'];
            }

            $orders = $this->helpdeskOrder->getOrderArray($email, $ticket->getCustomerId());
            foreach ($orders as $value) {
                $ordersOptions[] = ['name' => $value['name'], 'id' => $value['id'], 'url' => $value['url']];
            }
        }

        $config = [
            '_customer'        => [
                'id'     => $ticket->getCustomerId(),
                'email'  => $ticket->getCustomerEmail(),
                'cc'     => $ticket->getCc() ? implode(', ', $ticket->getCc()) : '',
                'bcc'    => $ticket->getBcc() ? implode(', ', $ticket->getBcc()) : '',
                'name'   => $ticket->getCustomerName(),
                'orders' => $ordersOptions,
            ],
            '_orderId'         => $ticket->getOrderId(),
            '_emailTo'         => $ticket->getCustomerEmail(),
            '_autocompleteUrl' => $this->getUrlManager()->getUrl('helpdesk/ticket/customerfind'),
        ];

        return $this->jsonEncoder->jsonEncode($config);
    }

    /**
     * @return string
     */
    public function getEditField()
    {
        $form     = $this->formFactory->create();
        $fieldset = $this->formElementFactory->create('fieldset', ['data' => ['legend' => __('General Information')]]);

        $fieldset->setForm($form);
        $fieldset->setId('edit_fieldset');
        $fieldset->setAdvanced(false);

        if ($this->config->getGeneralIsWysiwyg()) {
            $fieldset->addField('reply', 'editor', [
                'label'   => '',
                'name'    => 'reply',
                'class'   => 'hdmx__reply-area',
                'wysiwyg' => true,
                'config'  => $this->wysiwygConfig->getConfig(),
                'value'   => '',
            ]);
        } else {
            $fieldset->addField('reply', 'textarea', [
                'label' => __('Template'),
                'name'  => 'reply',
                'class' => 'hdmx__reply-area',
                'value' => '',
            ]);
        }
        $fieldset->addField('max-attachment-size', 'label', [
            'label' => __('Max file size: %1 Mb', $this->getAttachmentSize()),
            'name'  => 'max-attachment-size',
            'class' => 'attachment-size',
            'value' => '',
        ]);

        return $this->jsonEncoder->jsonEncode($fieldset->getChildrenHtml());
    }

    /**
     * @return float
     */
    public function getAttachmentSize()
    {
        return $this->fileSize->getMaxFileSizeInMb();
    }

    /**
     * @return string
     */
    public function getReplySwitcherJson()
    {
        $ticket = $this->getTicket();

        $config = [
            '_thirdPartyEmail' => $ticket->getThirdPartyEmail(),
        ];

        return $this->jsonEncoder->jsonEncode($config);
    }

    /**
     * @return string
     */
    public function getQuickRespoinsesJson()
    {
        $config = [
            'templates' => $this->getTemplatesArray(),
        ];

        return $this->jsonEncoder->jsonEncode($config);
    }

    /**
     * @return array
     */
    private function getTemplatesArray()
    {
        if (!$this->templates) {
            $ticket = $this->getTicket();

            $collection = $this->templateCollectionFactory->create()
                ->addFieldToFilter('is_active', 1)
                ->setOrder('name', 'asc');

            if ($ticket->getId()) {
                $collection->addStoreFilter($ticket->getStoreId());
            }

            $templates = [
                [
                    'id'   => 0,
                    'name' => __('Quick Responses'),
                    'body' => '',
                ],
            ];

            if ($collection->count()) {
                foreach ($collection as $template) {
                    $templates[] = [
                        'id'   => $template->getId(),
                        'name' => $template->getName(),
                        'body' => trim($template->getParsedTemplate($ticket)),
                    ];
                }
            }

            $this->templates = $templates;
        }

        return $this->templates;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\ResourceModel\Status\Collection|\Mirasvit\Helpdesk\Model\Status[]
     */
    public function getStatusCollection()
    {
        $ticket = $this->getTicket();

        return $this->statusFactory->create()->getPreparedCollection($ticket->getStoreId());
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Priority[]|\Mirasvit\Helpdesk\Model\ResourceModel\Priority\Collection
     */
    public function getPriorityCollection()
    {
        $ticket = $this->getTicket();

        return $this->priorityFactory->create()->getPreparedCollection($ticket->getStoreId());
    }

    /**
     * @return array
     */
    public function getAdminOwnerOptionArray()
    {
        $ticket = $this->getTicket();

        return $this->helpdeskHtml->getAdminOwnerOptionArray(false, $ticket->getStoreId());
    }

    /**
     * @return bool
     */
    public function isAllowDraft()
    {
        return $this->getConfig()->getDesktopIsAllowDraft();
    }

    /**
     * @return int
     */
    public function getDraftInterval()
    {
        return $this->getConfig()->getDesktopDraftUpdatePeriod();
    }

    /**
     * @return string
     */
    public function getDrafUpdateUrl()
    {
        return $this->getUrl('helpdesk/draft/update');
    }

    /**
     * @return string
     */
    public function getDraftText()
    {
        $text   = '';
        $ticket = $this->getTicket();

        if ($ticket && $ticket->getId() && $draft = $this->helpdeskDraft->getSavedDraft($this->getTicket()->getId())) {
            $text = $draft->getBody();
        }

        return $text;
    }
}
