<?php

namespace Nilesh\MirasvitHelpdeskCustomerImage\Block\Adminhtml;

use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Amasty\Customform\Model\ResourceModel\Answer\CollectionFactory as AnswerCollection;    


class CategoryUploadsTab extends \Magento\Backend\Block\Template implements TabInterface
{
    protected $_coreRegistry;

    /**
     * @var string
     */
    protected $_template = 'Nilesh_MirasvitHelpdeskCustomerImage::tab/category_uploads_tab.phtml';
    protected $orderCollection;
    protected $answerCollection;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        OrderCollectionFactory $orderCollection,
        AnswerCollection $answerCollection,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        $this->orderCollection = $orderCollection;
        $this->answerCollection = $answerCollection;
        parent::__construct($context, $data);
    }


    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Category Question Upload');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Category Question Upload');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }


    public function getCustomerOrder()
    {
        $customerId = $this->getCustomerId(); // pass customer id
        $customerOrder = $this->orderCollection->create()
            ->addFieldToFilter('customer_id', $customerId);
        return $customerOrder;
    }

    public function getCategoryAnswer($ip)
    {
        $answerCollection = $this->answerCollection->create()
        ->addFieldToFilter('ip', $ip);
        return $answerCollection->getData();
    }


}
?>