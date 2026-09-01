<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Direct;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class ThreeSecureInfo extends Action
{

    protected $formKeyValidator;

    protected $checkoutSession;

    public function __construct(
        Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var  $result Json */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setData([
                'error' => true,
                'error_msg' => __("Invalid Form Key")
            ]);
        }
        if ($this->getRequest()->isAjax()) {
            try {
                $data = $this->get3DSecureResponseData();
                $result->setData($data);
            } catch (\Exception $e) {
                return $result->setData([
                    'error' => true,
                    'message' => __("Payment exception")
                ]);
            }
        }

        return $result;
    }

    public function get3DSecureResponseData()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();
        $data = json_decode($payment->getAdditionalInformation('3d_secure_response'), true);
        if ($data) {
            $data['is3dSecure'] = true;
            $data['success'] = true;
            $data['threeDSSessionData'] = isset($data['VPSTxId']) ? $data['VPSTxId'] : '';
            return $data;
        } else {
            return [
                'is3dSecure' => false,
                'success' => true
            ];
        }
    }
}
