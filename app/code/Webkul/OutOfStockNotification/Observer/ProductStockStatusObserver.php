<?php
/**
 * Webkul Software
 *
 * @category    Webkul
 * @package     Webkul_ OutOfStockNotification
 * @author      Webkul
 * @copyright   Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license     https://store.webkul.com/license.html
 */
namespace Webkul\OutOfStockNotification\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use Webkul\OutOfStockNotification\Logger\Logger;

/**
 * Webkul OutOfStocknotification Product Stock Status Observer Model.
 */
class ProductStockStatusObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * customer model
     * @var \Magento\Customer\Model\Customer
     */
    private $customerModel;

    /**
     * helper
     * @var \Webkul\OutOfStockNotification\Helper\Data
     */
    private $helper;

    /**
     * product catelog factory
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $proFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $getRequest;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     * @param \Magento\Customer\Model\CustomerFactory             $customerModel
     * @param \Webkul\OutOfStockNotification\Model\ProductFactory $proFactory
     * @param \Magento\Framework\App\RequestInterface             $getRequest
     * @param \Webkul\OutOfStockNotification\Helper\Data          $helper
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\CustomerFactory $customerModel,
        \Webkul\OutOfStockNotification\Model\ProductFactory $proFactory,
        \Magento\Framework\App\RequestInterface $getRequest,
        \Webkul\OutOfStockNotification\Helper\Data $helper,
        \Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite $getStockId,
        \Magento\InventoryApi\Api\StockRepositoryInterface $stockRepository,
        \Magento\Inventory\Model\ResourceModel\StockSourceLink\CollectionFactory $stockSourceLinkFactory,
        \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\InventoryCatalogAdminUi\Model\GetSourceItemsDataBySku $sourceDataBySku,
        Logger $logger
    ) {
        $this->getRequest = $getRequest;
        $this->scopeConfig = $scopeConfig;
        $this->customerModel = $customerModel;
        $this->proFactory = $proFactory;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->getStockId = $getStockId;
        $this->stockSourceLinkFactory = $stockSourceLinkFactory;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->stockRepository = $stockRepository;
        $this->stockSourceLinkFactory = $stockSourceLinkFactory;
        $this->productFactory = $productFactory;
        $this->sourceDataBySku = $sourceDataBySku;
    }

    /**
     * controller_action_catalog_product_save_entity_after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $isInStock=0;
        $typesForMSI = ['simple', 'virtual', 'downloadable'];
        try {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $senderName = $this->scopeConfig->getValue('notificationsettings/settings/adminname', $storeScope);
            $senderEmail = $this->scopeConfig->getValue('notificationsettings/settings/adminemail', $storeScope);
            $notificationMethod = $this->scopeConfig->getValue('notificationsettings/settings/options', $storeScope);
            if ($notificationMethod == 1) {
                $helper = $this->helper;
                $data = $observer->getProduct();
                $sData = $data->getQuantityAndStockStatus();
                $sources = $this->sourceDataBySku->execute($data->getSku());
                if(!is_array($sData)){
                    $stockData['is_in_stock'] = $sData;
                  }
                  else{
                      $stockData = $sData;
                  }
                if (!empty($stockData)) {
                    if (array_key_exists('is_in_stock', $stockData)) {
                        $isInStock = $stockData['is_in_stock'];
                    }
                }
                if (!empty($data->getId())) {
                    $productId = $data->getId();
                    $productName = $data->getName();
                    $collection = $this->proFactory->create()
                                        ->getCollection()
                                        ->addFieldToFilter('product_id', ['eq'=>$productId])
                                        ->addFieldToFilter('status', ['eq'=>'0']);
                    $productUrl = $helper->getFrontendProductUrl($productId);
                   
                    $this->refractor(
                        $collection,
                        $isInStock,
                        $data,
                        $senderEmail,
                        $senderName,
                        $productName,
                        $productUrl,
                        $helper,
                        $sources
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->addError(' error in observer= ProductStockStatusObserver: '.$e->getMessage());
        }
    }

    /**
     * get customer name
     *
     * @param CustomerFactory $customer
     * @return string
     */
    private function getCustomerName($customer)
    {
        foreach ($customer as $customerInfo) {
            $customerName=$customerInfo->getFirstname();
        }
        if (empty($customerName)) {
            $custName = __("buyer");
        } else {
            $custName = $customerName;
        }
        return $custName;
    }

    /**
     * save collection
     *
     * @param ProductFactory $coll
     * @return void
     */
    private function saveCollection($coll)
    {
        $coll->save();
    }
    private function refractor(
        $collection,
        $isInStock,
        $data,
        $senderEmail,
        $senderName,
        $productName,
        $productUrl,
        $helper,
        $sources
    ) {
        foreach ($collection as $coll) {

            //condition to check if product belongs to type which support MSI
            if (count($sources) > 1) {
                $code = $coll->getWebsiteCode();
                $stockId = $this->getStockId->execute($code);
                $stockSourceLinkCollection = $this->stockSourceLinkFactory->create();
                $sourceLinks = $stockSourceLinkCollection->addFieldToFilter('stock_id', ['eq'=>$stockId]);
               
                foreach ($sourceLinks as $sourceLink) {
                    foreach ($sources as $source) {
                        if ($sourceLink->getSourceCode()==$source['source_code']) {
                            $isInStock= $isInStock+$source['status'];
                        }
                    }
                }
            }
            if ($isInStock >= 1) {
           
                $customerName = "";
                $customerEmail = $coll->getEmail();
                $senderInfo = [];
                $receiverInfo = [];
                $customer=$this->customerModel
                           ->create()
                           ->getCollection()
                           ->addFieldToFilter(
                               'email',
                               ['eq'=>$coll->getEmail()]
                           );
                $custName = $this->getCustomerName($customer);
                $receiverInfo = [
                'name' => $custName,
                'email' => $customerEmail,
                ];
                $senderInfo = [
                   'name' => $senderName,
                   'email' => $senderEmail,
                ];
                $emailTempVariables = [];
                $emailTempVariables['customerName'] = $custName;
                $emailTempVariables['productName'] = $productName;
                $emailTempVariables['adminName'] = $senderName;
                $emailTempVariables['productUrl'] = $productUrl;
                $helper->productInStockNotification(
                    $emailTempVariables,
                    $senderInfo,
                    $receiverInfo
                );
                $coll->setStatus('1');
                $this->saveCollection($coll);
            }
        }
    }
}
