<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AdminActionLog
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\AdminActionLog\Plugin;

use Bss\AdminActionLog\Helper\Data;
use Bss\AdminActionLog\Model\Log;

class Action
{
    /**
     * @var Log
     */
    protected $logAction;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * Action constructor.
     * @param Log $logAction
     * @param Data $helperData
     */
    public function __construct(
        \Bss\AdminActionLog\Model\Log   $logAction,
        \Bss\AdminActionLog\Helper\Data $helperData
    ) {
        $this->logAction = $logAction;
        $this->helperData = $helperData;
    }

    /**
     * Around Dispatch
     *
     * @param \Magento\Framework\App\ActionInterface $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @return mixed
     */
    public function aroundDispatch(
        \Magento\Framework\App\ActionInterface  $subject,
        \Closure                                $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $before = $request->getBeforeForwardInfo();
        $actionName = $request->getActionName();
        if (strcmp($actionName, 'gridToCsv') == 0 || strcmp($actionName, 'gridToXml') == 0) {
            $actionName = 'export';
        }
        $actionName = (str_contains(strtolower($actionName), 'save')) ? 'save' : $actionName;
        if (!(array_key_exists($actionName, $this->helperData->getActionTypeOption()))) {
            $actionName = 'view';
        }

        if (empty($before)) {
            $fullActionName = $request->getFullActionName();
        } else {
            $fullActionName = [$request->getRouteName()];

            if (isset($before['controller_name'])) {
                $fullActionName[] = $before['controller_name'];
            } else {
                $fullActionName[] = $request->getControllerName();
            }

            if (isset($before['action_name'])) {
                $fullActionName[] = $before['action_name'];
            } else {
                $fullActionName[] = $actionName;
            }

            $fullActionName = implode('_', $fullActionName);
        }
        $fullActionName = str_replace('adminhtml_order_shipment', 'sales_order_shipment', $fullActionName);
        $this->logAction->initAction($fullActionName, $actionName);
        if (strpos($fullActionName, 'export') !== false || strpos($fullActionName, 'print') !== false) {
            $this->logAction->logAction();
        }
        return $proceed($request);
    }
}
