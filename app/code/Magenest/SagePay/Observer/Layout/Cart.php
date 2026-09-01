<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Observer\Layout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class Cart implements ObserverInterface
{
    protected $_logger;
    protected $_cart;
    protected $_helper;
    protected $productModel;
    protected $_serialize;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * Cart constructor.
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magenest\SagePay\Helper\Subscription $helper
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param LoggerInterface $loggerInterface
     */
    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magenest\SagePay\Helper\Subscription $helper,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        LoggerInterface $loggerInterface
    ) {
        $this->_serialize = $serialize;
        $this->productMetadata = $productMetadata;
        $this->_cart = $cart;
        $this->_helper = $helper;
        $this->_logger = $loggerInterface;
        $this->productModel = $productModel;
    }

    public function execute(Observer $observer)
    {
        $item = $observer->getEvent()->getQuoteItem();
        $buyInfo = $item->getBuyRequest();
        $addedItems = $this->_cart->getQuote()->getAllItems();
        $flag = $this->_helper->isSubscriptionItems($addedItems);
        $isConfigurableProduct = false;

        if ($flag && count($addedItems) == 2) {
            foreach ($addedItems as $key => $addedItem) {
                $product = $addedItem->getProduct();
                $productType = $product->getTypeId();
                if ($productType == "configurable") {
                    $isConfigurableProduct = true;
                    break;
                } else {
                    $isConfigurableProduct = false;
                }
            }
        }

        if ($flag && (count($addedItems) > 1) && $isConfigurableProduct == false) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Item with subscription option can be purchased standalone only")
            );
        }

        if ($options = $buyInfo->getAdditionalOptions()) {
            $additionalOptions = [];
            foreach ($options as $key => $value) {
                if ($value) {
                    $additionalOptions[] = [
                        'label' => $key,
                        'value' => $value
                    ];
                }
            }

            $productMetadata = $this->productMetadata;
            $version = $productMetadata->getVersion();
            if (version_compare($version, "2.2.0") < 0) {
                $item->addOption([
                    'product_id' => $item->getProductId(),//Missing data
                    'code' => 'additional_options',
                    'value' => $this->_serialize->serialize($additionalOptions)
                ]);
            } else {
                $item->addOption([
                    'product_id' => $item->getProductId(),//Missing data
                    'code' => 'additional_options',
                    'value' => json_encode($additionalOptions)
                ]);
            }
        }
    }
}
