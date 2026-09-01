<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Checkout;

use Magenest\SagepayLib\Classes\SagepayApiException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class MerchantSessionKey extends Action
{
    protected $_helper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    protected $checkoutSession;

    public function __construct(
        Context $context,
        \Magenest\SagePay\Helper\Data $helperData,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_helper = $helperData;
        $this->_formKeyValidator = $formKeyValidator;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            throw new SagepayApiException("Invalid Form Key");
        }
        $quote = $this->checkoutSession->getQuote();
        if ($this->getRequest()->isAjax()) {
            if ((!$quote) || (!$quote->getIsActive())) {
                return $result->setData([
                    'error' => true,
                    'error_msg' => __("Quote is not active")
                ]);
            }
            $payload = '{ "vendorName": "' . $this->_helper->getVendorName() . '" }';
            $url = $this->_helper->getPiEndpointUrl() . '/merchant-session-keys';
            $response = $this->_helper->sendCurlRequest($url, $payload);
            if ($response['status'] == 201) {
                return $result->setData([
                    'error' => false,
                    'success' => true,
                    'data' => $response['data']
                ]);
            } else {
                return $result->setData([
                    'error' => true,
                    'success' => false,
                    'data' => $response['data']
                ]);
            }
        }
    }
}
