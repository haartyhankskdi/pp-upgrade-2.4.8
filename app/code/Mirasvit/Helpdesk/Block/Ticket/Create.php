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


namespace Mirasvit\Helpdesk\Block\Ticket;

use Mirasvit\Helpdesk\Model\Config as Config;
use Mirasvit\Helpdesk\Model\PriorityFactory;
use Mirasvit\Helpdesk\Model\DepartmentFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory as TicketCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory as FieldCollectionFactory;
use Magento\Framework\File\Size;
use Mirasvit\Helpdesk\Helper\Field;
use Mirasvit\Helpdesk\Helper\Order;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Url;
use Magento\Framework\View\Element\Template\Context;

class Create extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Mirasvit\Helpdesk\Model\PriorityFactory
     */
    protected $priorityFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\DepartmentFactory
     */
    protected $departmentFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory
     */
    protected $ticketCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory
     */
    protected $fieldCollectionFactory;

    protected $fileSize;
    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    protected $config;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Field
     */
    protected $helpdeskField;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $context;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Order
     */
    protected $helpdeskOrder;
    /**
     * @var \Magento\Framework\Url
     */
    private $urlManager;

    public function __construct(
        PriorityFactory $priorityFactory,
        DepartmentFactory $departmentFactory,
        TicketCollectionFactory $ticketCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        FieldCollectionFactory $fieldCollectionFactory,
        Config $config,
        Size $fileSize,
        Field $helpdeskField,
        Order $helpdeskOrder,
        CustomerFactory $customerFactory,
        Session $customerSession,
        Url $urlManager,
        Context $context,
        array $data = []
    ) {
        $this->priorityFactory = $priorityFactory;
        $this->departmentFactory = $departmentFactory;
        $this->ticketCollectionFactory = $ticketCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->fieldCollectionFactory = $fieldCollectionFactory;
        $this->config = $config;
        $this->fileSize = $fileSize;
        $this->helpdeskField = $helpdeskField;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->helpdeskOrder = $helpdeskOrder;
        $this->urlManager = $urlManager;
        $this->context = $context;

        parent::__construct($context, $data);
    }

    /**
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->pageConfig->getTitle()->set(__('Create Ticket'));
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    protected function getCustomer()
    {
        return $this->customerFactory->create()->load($this->customerSession->getCustomerId());
    }

    /**
     * @return object
     */
    public function getPriorityCollection()
    {
        return $this->priorityFactory->create()->getPreparedCollection($this->context->getStoreManager()->getStore());
    }

    /**
     * @return object
     */
    public function getDepartmentCollection()
    {
        return $this->departmentFactory->create()->getPreparedCollection($this->context->getStoreManager()->getStore())
            ->addFieldToFilter('is_show_in_frontend', true);
    }

    /**
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrderCollection()
    {
        $collection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('customer_id', (int)$this->getCustomer()->getId());

        return $collection;
    }

    /**
     * @return object
     */
    public function getCustomFields()
    {
        $collection = $this->helpdeskField->getEditableCustomerCollection();

        return $collection;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Field $field
     * @return string
     */
    public function getInputHtml($field)
    {
        return $this->helpdeskField->getInputHtml($field);
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return object
     */
    public function getFrontendIsAllowPriority()
    {
        return $this->getConfig()->getFrontendIsAllowPriority();
    }

    /**
     * @return object
     */
    public function getFrontendIsAllowDepartment()
    {
        return $this->getConfig()->getFrontendIsAllowDepartment();
    }

    /**
     * @return object
     */
    public function getFrontendIsAllowOrder()
    {
        return $this->getConfig()->getFrontendIsAllowOrder();
    }

    /**
     * @param \Magento\Sales\Model\Order|int $order
     * @param bool|string $url
     *
     * @return string
     */
    public function getOrderLabel($order, $url = false)
    {
        return $this->helpdeskOrder->getOrderLabel($order, $url);
    }

    /**
     * @return string
     */
    public function getSubmitUrl()
    {
        $urlManager = clone $this->urlManager;
        if ($id = $this->context->getStoreManager()->getStore()->getId()) {
            $urlManager->setScope($id);
        }

        return $urlManager->getUrl('helpdesk/ticket/postmessage');
    }

    /**
     * @return string
     */
    public function getOrdersUrl()
    {
        $urlManager = clone $this->urlManager;
        if ($id = $this->context->getStoreManager()->getStore()->getId()) {
            $urlManager->setScope($id);
        }

        return $urlManager->getUrl('helpdesk/ticket/loadorders');
    }

    /**
     * @return bool
     */
    public function isAttachmentEnabled()
    {
        return $this->getConfig()->getFrontendIsActiveAttachment();
    }

    /**
     * @return float
     */
    public function getAttachmentSize()
    {
        return $this->fileSize->getMaxFileSizeInMb();
    }
}
