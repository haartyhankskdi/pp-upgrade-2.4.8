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



namespace Mirasvit\Helpdesk\Block\Ticket;

use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Model\Config as Config;

class Listing extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory
     */
    protected $ticketCollectionFactory;

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
     * @var \Magento\Framework\Url
     */
    private $urlManager;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @param Config $config
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Url $urlManager
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\Config $config,
        \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Url $urlManager,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->config = $config;
        $this->ticketCollectionFactory = $ticketCollectionFactory;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->urlManager = $urlManager;
        $this->context = $context;

        parent::__construct($context, $data);
    }

    /**
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $title = $this->config->getDefaultFrontName($this->_storeManager->getStore()->getId());

        parent::_prepareLayout();
        $this->pageConfig->getTitle()->set($title);
        /** @var \Magento\Theme\Block\Html\Title|false $pageMainTitle */
        $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            $pageMainTitle->setPageTitle($title);
        }

        return $this;
    }

    /**
     * @return Listing\Column\DefaultColumn[]
     */
    public function getColumns()
    {
        $columns = [];

        $names = array_intersect($this->getChildNames(), $this->getGroupChildNames('column'));

        foreach ($names as $name) {
            $block = $this->getChildBlock($name);
            // getChildBlock returns false for a missing alias — only real column blocks qualify
            if ($block instanceof Listing\Column\DefaultColumn) {
                $columns[$name] = $block;
            }
        }

        return $columns;
    }

    /**
     * @param Listing\Column\DefaultColumn $column
     * @param TicketInterface $item
     * @return string
     */
    public function getColumnHtml(Listing\Column\DefaultColumn $column, TicketInterface $item)
    {
        $column->setItem($item);

        return $column->toHtml();
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    protected function getCustomer()
    {
        return $this->customerFactory->create()->load((int) $this->customerSession->getCustomerId());
    }

    /**
     * @return object
     */
    public function getTicketCollection()
    {
        $collection = $this->ticketCollectionFactory->create()
            ->addFieldToFilter('customer_id', $this->getCustomer()->getId())
            ->addFieldToFilter('folder', ['neq' => Config::FOLDER_SPAM]);

        return $collection;
    }

    /**
     * @return string
     */
    public function getCreateUrl()
    {
        $urlManager = clone $this->urlManager;
        if ($id = $this->context->getStoreManager()->getStore()->getId()) {
            $urlManager->setScope($id);
        }

        return $urlManager->getUrl('helpdesk/ticket/create');
    }
}
