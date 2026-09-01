<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model;

class SagePayForm extends AbstractSage
{
    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return SagePayForm
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $response = $this->sageHelper->refund($payment, $amount);
        return parent::refund($payment, $amount);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return SagePayForm
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $response = $this->sageHelper->voidTransaction($payment);
        return parent::void($payment);
    }

    /**
     * @param array $debugData
     */
    protected function _debug($debugData)
    {
        $this->sageLogger->debug(var_export($debugData, true));
    }
}
