<?php
namespace Nilesh\GeneralQuestions\Block\Adminhtml\CustomerEdit\Tab;
use Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions\Collection as GeneralQuestions;

class View extends \Magento\Backend\Block\Template implements \Magento\Ui\Component\Layout\Tabs\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'tab/customer_view.phtml';
    protected $generalQuestions;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        GeneralQuestions $generalQuestions,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->generalQuestions = $generalQuestions;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(\Magento\Customer\Controller\RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('General Question');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('General Question');
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

    public function getMediaGPUrl()
    {
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of\Magento\Framework\App\ObjectManager
        $storeManager = $_objectManager->get('Magento\Store\Model\StoreManagerInterface'); 
        $currentStore = $storeManager->getStore();
        return $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getCustomerData()
    {
        /**
         * Step to do so
         * 1. Need customer id
         * 2. Need to get custom question
         */
        $customerId = $this->getCustomerId();
        $model = $this->generalQuestions->addFieldToFilter("customer_id", $customerId)->setPageSize(1)->setOrder('generalquestions_id', 'DESC')->load();
        return $model->getData();
    }
}