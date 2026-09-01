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
namespace Bss\AdminActionLog\Observer;

use Bss\AdminActionLog\Model\Log;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ModelSaveBefore implements ObserverInterface
{
    /**
     * @var \Bss\AdminActionLog\Model\Log
     */
    protected $logAction;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * ModelSave constructor.
     * @param Log $logAction
     * @param RequestInterface $request
     */
    public function __construct(
        \Bss\AdminActionLog\Model\Log $logAction,
        RequestInterface $request
    ) {
        $this->logAction = $logAction;
        $this->request=$request;
    }

    /**
     * Event before save
     *
     * @param Observer $observer
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $object=$observer->getEvent()->getObject();
        $action = $this->logAction->getAction();
        if (is_array($action) && array_key_exists('expected_models', $action)) {
            if (is_string($action['expected_models']) && ($action['expected_models']) == get_class($object)) {
                if ($object->getOrigData() === null && $object->getId() !== null) {
                    $requestParams=$this->request->getParams();
                    foreach ($object->getData() as $key => $value) {
                        if (!array_key_exists($key, $requestParams)) {
                            $value = ($value===false) ? 0 : $value;
                            $this->request->setParam($key, $value);
                        }
                    }
                    $orgData= $object->load($object->getId())->getData();
                    if (is_array($orgData)) {
                        foreach ($orgData as $key => $value) {
                            $observer->getEvent()->getObject()->setOrigData($key, $value);
                        }
                    }
                    foreach ($this->request->getParams() as $item => $value) {
                        if (array_key_exists($item, $object->getData())) {
                            $observer->getEvent()->getObject()->setData($item, $value);
                        }
                    }
                }
            }
        }
    }
}
