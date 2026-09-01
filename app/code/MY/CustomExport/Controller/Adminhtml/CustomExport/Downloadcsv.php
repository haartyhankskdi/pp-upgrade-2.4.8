<?php

namespace MY\CustomExport\Controller\Adminhtml\CustomExport;

use Magento\Backend\App\Action;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\ProductFactory as ModelFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Downloadcsv extends Action
{
    protected $fileFactory;
    protected $collectionFactory;
    protected $productModel;
    protected $timezone;

    public function __construct(
        Action\Context $context,
        FileFactory $fileFactory,
        \MY\CustomExport\Model\ResourceModel\CustomExport\CollectionFactory $collectionFactory,
        ModelFactory $productModel,
        TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->collectionFactory = $collectionFactory;
        $this->productModel = $productModel;
        $this->timezone = $timezone;
    }

    public function execute()
    {
        $dateFrom = $this->getRequest()->getParam('date_from');
        $dateTo = $this->getRequest()->getParam('date_to'); 
        $product_name = $this->getRequest()->getParam('name');
        $gender = $this->getRequest()->getParam('gender');
        $coupon_code = $this->getRequest()->getParam('coupon_code');
        $customer_email = $this->getRequest()->getParam('email');

        $customerGender = ($gender == "Male") ? "1" : "2";

        try {
            $collection = $this->collectionFactory->create();
            if (!empty($gender)) {
                $collection->addFieldToFilter('customer_gender', $customerGender);
            }
            if (!empty($product_name)) {
                $collection->addFieldToFilter('name', ['like' => '%' . $product_name . '%']);
            }
            if (!empty($coupon_code)) {
                $collection->addFieldToFilter('coupon_code', ['like' => '%' . $coupon_code . '%']);
            }
            if (!empty($customer_email)) {
                $collection->addFieldToFilter('customer_email', ['like' => '%' . $customer_email . '%']);
            }

           // Timezone-aware date filtering (fixes "starts from 1am instead of midnight" issue)
            if (!empty($dateFrom) && !empty($dateTo)) {
                $configTimezone = $this->timezone->getConfigTimezone(); // e.g. 'Europe/London'

                // Local midnight (start of day) -> UTC
                $localFrom = new \DateTime($dateFrom . ' 00:00:00', new \DateTimeZone($configTimezone));
                $localFrom->setTimezone(new \DateTimeZone('UTC'));
                $utcDateFrom = $localFrom->format('Y-m-d H:i:s');

                // Local end of day (23:59:59) -> UTC
                $localTo = new \DateTime($dateTo . ' 23:59:59', new \DateTimeZone($configTimezone));
                $localTo->setTimezone(new \DateTimeZone('UTC'));
                $utcDateTo = $localTo->format('Y-m-d H:i:s');

                $collection->addFieldToFilter('created_at', ['gteq' => $utcDateFrom]);
                $collection->addFieldToFilter('created_at', ['lteq' => $utcDateTo]);
            }

            if ($collection->getSize() === 0) {
                $this->messageManager->addErrorMessage(__('No data found for the given filters.'));
                return $this->resultRedirectFactory->create()->setPath('customexport/customexport/Exportdata');
            }

            $fileName = 'custom_report.csv';
            $stream = fopen('php://temp', 'r+');
            if (!$stream) {
                $this->messageManager->addErrorMessage(__('Error: Unable to create file stream.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }

            $header = [
                'Unique ID',
                'Country', 'Order ID', 'Purchase Date', 'Bill to Name', 'Ship to Name', 'Grand Total', 
                'Status', 'Shipping Info', 'Customer Email', 'Subtotal', 'Shipping Fee', 'QTY', 'Tax Amount',
                'Tax Percent', 'Discount Amount', 'SKU', 'DOB', 'Prescriber Name', 'Gender', 'Customer Group', 'Ethnic Group', 'Sub Ethnicity', 
                'Billing Address', 'Shipping Address', 'Configurable Product', 'Associated Product', 'Price', 'Brand', 'Medical Strength', 'Size',
                'Shipping Tracking No', 'Subscribed to Newsletter', 'Transaction ID', 'Vendor Transaction Code', 
                'Coupon Code', 'Qnair Unique Id'
            ];
            fputcsv($stream, $header);

            foreach ($collection as $item) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $customer_data= $objectManager->create('Magento\Customer\Model\Customer')->load($item->getCustomerId());
                $orderId = $item->getIncrementId();
                $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
             $prescriberid = $order->getData('prescriber_name');
            $prescriberModel = $objectManager->create('Nilesh\PrescriberName\Model\PrescriberName')->load($prescriberid);
            $prescriber_name= $prescriberModel->getData('name');
            $payment = $order->getPayment();
                $transactionId = $payment ? $payment->getLastTransId() : null;
                $vendorTxCode = $payment ? $payment->getAdditionalInformation('vendor_tx_code') : null;
                $billingAddress = $order->getBillingAddress();
                $shippingAddress = $order->getShippingAddress();

                $billingAddressText = $billingAddress ? implode(', ', array_filter($billingAddress->getStreet())) . ', '
                    . $billingAddress->getCity() . ', '
                    . $billingAddress->getRegion() . ', '
                    . $billingAddress->getPostcode() . ', '
                    . $billingAddress->getCountryId() : '';

                $shippingAddressText = $shippingAddress ? implode(', ', array_filter($shippingAddress->getStreet())) . ', '
                    . $shippingAddress->getCity() . ', '
                    . $shippingAddress->getRegion() . ', '
                    . $shippingAddress->getPostcode() . ', '
                    . $shippingAddress->getCountryId() : '';
            
                    $customerId = $item->getCustomerId();
                if ($order->getId()) {
                    foreach ($order->getAllVisibleItems() as $orderItem) {
                        $product_name = $this->getProductNameBySku($orderItem->getSku()) ;
                         // Safe, human-readable date formatting (store timezone aware)
                         $createdAt = $item->getCreatedAt();
                         $formattedDate = '';
                         if (!empty($createdAt)) {
                             try {
                                 $formattedDate = $this->timezone->date(new \DateTime($createdAt))->format('M j, Y g:i:s A');
                             } catch (\Exception $e) {
                                 $formattedDate = $createdAt; // fallback if parsing fails
                             }
                         }
 
                        $csvRow = [
                            $customerId,
                            $item->getCountryId(),
                            $item->getIncrementId(),
                            $this->timezone->date(new \DateTime($item->getCreatedAt()))->format('Y-m-d H:i:s'),
                            $item->getBillName(),
                            $item->getShipName(),
                            $item->getBaseGrandTotal(),
                            $item->getStatus(),
                            $item->getShippingDescription(),
                            $item->getCustomerEmail(),
                            //$item->getSubtotal(),
                            //$item->getSubtotal(),
                            number_format($orderItem->getPrice(), 2),
                            $item->getBaseShippingAmount(),
                            $orderItem->getQtyOrdered(),
                            $item->getBaseTaxAmount(),
                            $item->getData('taxpercent'),
                            $item->getBaseDiscountAmount(),
                            $orderItem->getSku(),
                            $item->getCustomerDob(),
                            //$item->getData('prescriber_name'),
                            $prescriber_name,
                            ($item->getData('customer_gender') == 1) ? 'Male' : 'Female',
                            $item->getData('customer_group_id'),
                            $customer_data->getData('ethnicitycust'),
                            $customer_data->getData('sub_ethnicitycust'),
                            $billingAddressText,
                            $shippingAddressText,
                            $orderItem->getName(),
                            $product_name,
                            number_format($orderItem->getPrice(), 2),
                            $item->getBrand(),
                            $item->getMedicineStrength(),
                            $item->getSize(),
                            $item->getTrackNumber(),
                            $item->getSubscriberStatus(),
                            $transactionId,
                            $vendorTxCode,
                            $item->getCouponCode(),
                            $item->getData('questionnaire_unique_id'),
                        ];
                        fputcsv($stream, $csvRow);
                    }
                }
            }

            rewind($stream);
            $content = stream_get_contents($stream);
            fclose($stream);

            return $this->fileFactory->create(
                $fileName,
                $content,
                DirectoryList::VAR_DIR,
                'text/csv'
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
    }

    /**
     * Get product name from SKU using ProductFactory.
     *
     * @param string $sku
     * @return string|null
     */
    public function getProductNameBySku($sku)
    {
        try {
            if (!$this->productModel) {
                // if productFactory is not injected via DI, return null
                return null;
            }
            $product = $this->productModel->create()->loadByAttribute('sku', $sku);
            if ($product && $product->getId()) {
                return $product->getName();
            }
        } catch (\Exception $e) {
            // Handle exceptions if needed or log error
        }
        return null;
    }
}
