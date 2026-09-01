<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Helper;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    protected $fileName = '/var/log/sagepay/debug.log';
    protected $loggerType = \Monolog\Logger::DEBUG;
}
