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



namespace Mirasvit\Helpdesk\Block\Contact;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http;

class Kb extends Template
{
    /**
     * @var \Mirasvit\Kb\Model\ResourceModel\Article\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var SessionFactory
     */
    protected $_customerSession;

    /**
     * @var Context
     */
    protected $context;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var Http
     */
    private $request;

    /**
     * Kb constructor.
     * @param Context $context
     * @param Http $request
     * @param CustomerRepositoryInterface $customerRepository
     * @param SessionFactory $customerSession
     */
    public function __construct(
        Context $context,
        Http $request,
        CustomerRepositoryInterface       $customerRepository,
        SessionFactory $customerSession
    ) {
        $om = ObjectManager::getInstance();

        $this->collectionFactory  = $om->create('Mirasvit\Kb\Model\ResourceModel\Article\CollectionFactory');
        $this->context            = $context;
        $this->request            = $request;
        $this->customerRepository = $customerRepository;
        $this->_customerSession   = $customerSession;

        $this->setTemplate('Mirasvit_Helpdesk::contact/form/kb_result.phtml');

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Filter\FilterManager
     */
    public function getFilterManager()
    {
        return $this->filterManager;
    }

    /**
     * @return string
     */
    public function getSearchQuery()
    {
        return $this->context->getRequest()->getParam('s');
    }

    /**
     * @return \Mirasvit\Kb\Model\ResourceModel\Article\Collection
     */
    public function getCollection()
    {
        $search = $this->collectionFactory->create()->getSearchInstance();
        $customer = $this->_customerSession->create();

        $collection = $this->collectionFactory->create();

        $search->joinMatched($this->getSearchQuery(), $collection, 'main_table.article_id');
        $collection
            ->addStoreIdFilter($this->context->getStoreManager()->getStore()->getId())
            ->addFieldToFilter('main_table.is_active', true)
            ->addCustomerGroupIdFilter($customer->getCustomerGroupId());
        $collection->setPageSize(5);

        return $collection;
    }
}