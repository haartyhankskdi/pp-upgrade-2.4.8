<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model;

use Magento\Framework\Model\AbstractModel;

class Transaction extends AbstractModel
{
    protected $_eventPrefix = 'transaction_';
}
