<?php
namespace Haartyhanks\LNAPI\Block\Adminhtml\Edit\Tab;

// use Magento\Backend\Block\Admin\Formkey as AdminFormkey;
use Magento\Framework\Data\Form\FormKey;
 
class View extends \Magento\Backend\Block\Template implements \Magento\Ui\Component\Layout\Tabs\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'tab/customer_view.phtml';
    protected $customerRepository;
    protected $_addressFactory;
    protected $_formkey;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterfaceFactory $customerRepositoryFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        Formkey $_formkey,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->customerRepository = $customerRepositoryFactory->create();
        $this->_addressFactory = $addressFactory;
        $this->_formkey = $_formkey;
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
        return __('Lexis Nexis Verification');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Lexis Nexis Verification');
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

    // public function getAgeverifiedCustomer($customerid)
    // {
    //     $ageFactory = $this->ageFactory->create();
    //     $newsModel = $ageFactory->getCollection()->addFieldToFilter('customer_id', $customerid);
    //     $newsModel = $newsModel->getData();
    //     if(!empty($newsModel)){
    //     foreach ($newsModel as $key => $value) {
    //         $value;
    //     }
    //     return $value;
    //     }
    // }
    public function getDatanew()
    {
        //$this->getCustomerId()
        $customerData = $this->customerRepository->getById($this->getCustomerId());
         //return $cst;
        //$customerData = $this->_customerSession->getCustomer()->getId();//get id of customer
        return $customerData;
    }

    public function getBillingAddress()
    {
    //$customerData = $this->customerSession->getDefaultBilling();
        //billing
        $customer = $this->customerRepository->getById($this->getCustomerId());
    //$billingAddressId = $this->customerSession->getCustomer()->getDefaultBilling();
    $billingAddressId = $customer->getDefaultBilling(); 
    $billingAddress = $this->_addressFactory->create()->load($billingAddressId);
    return $billingAddress->getData();
    //return $billingAddress;
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}