<?php
namespace Magenest\SagePay\Block\Checkout;

class Iframe extends \Magento\Framework\View\Element\Template
{
    protected $_storeManager;
    protected $_urlInterface;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        parent::__construct($context, $data);
    }

    /**
     * Prining URLs using StoreManagerInterface
     */
    public function getStoreManagerData()
    {
        return $this->_storeManager->getStore()->getCurrentUrl(false);
    }

    /**
     * Prining URLs using URLInterface
     */
    public function getUrlInterfaceData()
    {
        return $this->_urlInterface->getCurrentUrl();
    }
}
