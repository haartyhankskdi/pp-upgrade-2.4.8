<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Helper;

use Magenest\SagepayLib\Classes\SagepayAbstractApi;
use Magenest\SagepayLib\Classes\SagepayApiManager;
use Magenest\SagepayLib\Classes\SagepayBasket;
use Magenest\SagepayLib\Classes\SagepayCustomer;
use Magenest\SagepayLib\Classes\SagepayCustomerDetails;
use Magenest\SagepayLib\Classes\SagepayDiscount;
use Magenest\SagepayLib\Classes\SagepayItem;
use Magenest\SagepayLib\Classes\SagepaySettings;

class SagepayAPI
{
    /**
     * @var string
     */
    protected $integrationType = '';

    /**
     * @var SagepaySettings
     */
    protected $sagepayConfig;

    /**
     * @var Data
     */
    protected $_data;

    /**
     * SagepayAPI constructor.
     * @param Data $data
     * @param SagepaySettings $sagepayConfig
     * @param $integrationType
     */
    public function __construct(
        Data $data,
        SagepaySettings $sagepayConfig,
        $integrationType
    ) {
        $this->_data = $data;
        $this->sagepayConfig = $sagepayConfig;
        $this->integrationType = $integrationType;
    }

    /**
     * Set SagepaySettings for controller
     *
     * @param SagepaySettings $sagepayConfig
     */
    public function setSagepayConfig(SagepaySettings $sagepayConfig)
    {
        $this->sagepayConfig = $sagepayConfig;
    }

    /**
     * Build Api for transaction
     * @param \Magento\Quote\Model\Quote $quote
     * @return SagepayAbstractApi
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function buildApi($quote, $details)
    {
        $basket = $this->getBasket($quote);
        $basket->setDescription("Payment");
        if ($quote->getIsVirtual()) {
            $fields = [
                'Firstnames', 'Surname', 'Address1', 'Address2', 'City', 'PostCode', 'Country', 'State', 'Phone'
            ];
            $details = $this->setDefaultDelivery($details, $fields);
            $basket->setDeliveryNetAmount("0");
            $basket->setDeliveryTaxAmount("0");
        } else {
            $shippingAddress = $quote->getShippingAddress();
            $basket->setDeliveryNetAmount($shippingAddress->getBaseShippingAmount());
            $basket->setDeliveryTaxAmount($shippingAddress->getBaseShippingTaxAmount());

        }
        $discounts = [];
        $baseSubTotal = $quote->getBaseSubtotal();
        $baseSubTotalWithDiscount = $quote->getBaseSubtotalWithDiscount();
        $discountAmount = $baseSubTotal-$baseSubTotalWithDiscount;
        if ($discountAmount>0) {
            $cartDiscount = new SagepayDiscount();
            $cartDiscount->setFixed($discountAmount);
            $cartDiscount->setDescription("Discount Code");
            $discounts[] = $cartDiscount;
        }
        if ($quote->getData('use_customer_balance')) {
            $storeCreditDiscount = new SagepayDiscount();
            $storeCreditDiscount->setFixed($quote->getData('base_customer_bal_amount_used'));
            $storeCreditDiscount->setDescription("Store Credit");
            $discounts[] = $storeCreditDiscount;
        }
        if ($quote->getData('base_gift_cards_amount_used')) {
            $giftCardDiscount = new SagepayDiscount();
            $giftCardDiscount->setFixed($quote->getData('base_gift_cards_amount_used'));
            $giftCardDiscount->setDescription("Gift Card");
            $discounts[] = $giftCardDiscount;
        }

        if ($discounts) {
            $basket->setDiscounts($discounts);
        }

        $basket->setAmount($this->_data->getPayAmount($quote));

        $api = SagepayApiManager::create($this->integrationType, $this->sagepayConfig);

        if ($api === null) {
            return false;
        }

        $xml = $basket->exportAsXml(true);
        $api->setBasket($basket);
        $cardType = $details['cardType'] ?? null;
        $cardTypePayPal = $details['CardType'] ?? null;
        if (isset($cardType)) {
            $card = [
                "cardType" => $details['cardType'],
                "cardNumber" => $details['cardNumber'],
                "cardHolder" => $details['cardHolder'],
                "startDate" => "1010",
                "expiryDate" => $details['expiryDate'],
                "cv2" => $details['cv2'],
            ];
            $api->setPaneValues($card);
        }

        if ($cardTypePayPal == 'PAYPAL') {
            $card = [
                "cardType" => 'PAYPAL',
                "cardNumber" => null,
                "cardHolder" => null,
                "startDate" => null,
                "expiryDate" => null,
                "cv2" => null,
            ];
            $api->setPaneValues($card);
        }

        // Add billing and delivery details
        $address1 = $this->createCustomerDetails($details, 'billing');
        $api->addAddress($address1);

        $address2 = $this->createCustomerDetails($details, 'delivery');
        $api->addAddress($address2);

        $account = false;
        if ($account) {
            $customer = new SagepayCustomer();
            $customer->setCustomerId($account['id']);
            $api->setCustomer($customer);
        }
        $api->updateData($details);
        return $api;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     */
    private function getBasket($quote)
    {
        $basket = new SagepayBasket();
        $quoteItems = $quote->getAllVisibleItems();
        foreach ($quoteItems as $qitem) {
            if ($qitem->getParentItem()) {
                continue;
            }
            $item = new SagepayItem();
            $productName = $this->cleanProductSku($qitem->getName());
            $productName = substr($productName, 0, 100);
            $productSku = $this->cleanProductSku($qitem->getSku());
            $productSku = substr($productSku, 0, 12);
            $item->setDescription($productName);
            $item->setProductSku($productSku);
            if ($qitem->getQtyOrdered()) {
                $item->setUnitTaxAmount($qitem->getBaseTaxAmount()/$qitem->getQtyOrdered());
                $item->setQuantity($qitem->getQtyOrdered());
            } else {
                $item->setUnitTaxAmount($qitem->getBaseTaxAmount()/$qitem->getQty());
                $item->setQuantity($qitem->getQty());
            }
            $item->setUnitNetAmount($qitem->getBasePrice());
            $basket->addItem($item);
        }
        return $basket;
    }

    /**
     * Create and populate customer details
     *
     * @param array $data
     * @param array $type
     * @return SagepayCustomerDetails
     */
    protected function createCustomerDetails($data, $type)
    {
        $customerdetails = new SagepayCustomerDetails();
        $keys = $this->getDefaultCustomerKeys($type);
        foreach ($keys as $key => $value) {
            if (isset($data[$key])) {
                $customerdetails->$value = $data[$key];
            }
            if (isset($data[ucfirst($key)])) {
                $customerdetails->$value = $data[ucfirst($key)];
            }
        }
        if ($type == 'billing' && isset($data['customerEmail'])) {
            $customerdetails->email = $data['customerEmail'];
        }
        return $customerdetails;
    }

    /**
     * Define default customer keys
     *
     * @param string $type
     * @return string[]
     */
    protected function getDefaultCustomerKeys($type)
    {
        $result = [];
        $keys = [
            'Firstnames' => 'firstname',
            'Surname' => 'lastname',
            'Address1' => 'address1',
            'Address2' => 'address2',
            'City' => 'city',
            'PostCode' => 'postcode',
            'Country' => 'country',
            'State' => 'state',
            'Phone' => 'phone'
        ];

        foreach ($keys as $key => $value) {
            $result[$type . $key] = $value;
        }
        return $result;
    }

    /**
     * Set default delivery from billing details
     *
     * @param array $data
     * @param array $keys
     *
     * @return array
     */
    protected function setDefaultDelivery($data, $keys)
    {
        foreach ($keys as $key) {
            if (isset($data['Billing' . $key])) {
                $data['Delivery' . $key] = $data['Billing' . $key];
            }
        }
        return $data;
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }

    public function setPaypalCallbackUrl($url)
    {
        $this->sagepayConfig->setPaypalCallbackUrl($url);
    }

    public function getPurchaseUrl($method, $env = '')
    {
        return $this->sagepayConfig->getPurchaseUrl($method, $env);
    }

    private function cleanProductSku($text)
    {
        $pattern = '|[^a-zA-Z0-9\-\+\ ]+|';
        $text = preg_replace($pattern, '', $text);
        return $text;
    }
}
