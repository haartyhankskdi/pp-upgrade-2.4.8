<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model;

use Magento\Payment\Model\CcGenericConfigProvider;

class ConfigProvider extends CcGenericConfigProvider
{
    protected $methodCodes = [
        SagePay::CODE,
    ];
}
