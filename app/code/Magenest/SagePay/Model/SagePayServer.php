<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model;

class SagePayServer extends AbstractSage
{
    const CODE = 'magenest_sagepay_server';

    protected $_code = self::CODE;
    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return SagePayServer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $response = $this->sageHelper->refund($payment, $amount);
        return parent::refund($payment, $amount);
    }

    protected function _debug($debugData)
    {
        $this->sageLogger->debug(var_export($debugData, true));
    }
}
