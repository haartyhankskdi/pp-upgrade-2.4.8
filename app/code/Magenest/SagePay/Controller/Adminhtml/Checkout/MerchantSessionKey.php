<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Checkout;

use Magento\Backend\App\Action;

class MerchantSessionKey extends \Magento\Backend\App\Action
{
    protected $helperData;

    public function __construct(
        Action\Context $context,
        \Magenest\SagePay\Helper\Data $helperData
    ) {
        $this->helperData = $helperData;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        if ($this->getRequest()->isAjax()) {
            $payload = '{ "vendorName": "' . $this->helperData->getVendorName() . '" }';
            $url = $this->helperData->getPiEndpointUrl() . '/merchant-session-keys';
            $response = $this->helperData->sendCurlRequest($url, $payload);
            if ($response['status'] == 201) {
                return $result->setData([
                    'merchantSessionKey' => isset($response['data']) ? $response['data']->merchantSessionKey : '',
                    'success' => true,
                ]);
            } else {
                return $result->setData([
                    'error' => true,
                    'success' => false
                ]);
            }
        }
        return $result->setData([
            'error' => true,
            'success' => false
        ]);
    }
}
