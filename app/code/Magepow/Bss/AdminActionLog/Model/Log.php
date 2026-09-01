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

namespace Bss\AdminActionLog\Model;

use Bss\AdminActionLog\Helper\Data;
use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\User\Model\User;
use Psr\Log\LoggerInterface;

/**
 * Class Log
 * @package Bss\AdminActionLog\Model
 */
class Log
{
    /**
     * @var
     */
    protected $action;

    /**
     * @var string
     */
    protected $actionName = '';

    /**
     * @var array
     */
    protected $logDetails = [];

    /**
     * @var Session
     */
    protected $authSession;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var IpAdress
     */
    protected $ipAddress;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var PostDispatch
     */
    protected $postdispatch;

    /**
     * @var ActionGridFactory
     */
    protected $actionLog;

    /**
     * @var ActionDetailFactory
     */
    protected $logdetail;

    /**
     * @var LoginFactory
     */
    protected $loginlog;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var null
     */
    protected $name = null;

    /**
     * @var bool
     */
    protected $skipNextAction = false;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var bool
     */
    protected $sendmail = false;

    /**
     * Log constructor.
     *
     * @param Session $authSession
     * @param ManagerInterface $messageManager
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param User $user
     * @param IpAdress $ipAddress
     * @param PostDispatch $postdispatch
     * @param ActionGridFactory $actionLog
     * @param ActionDetailFactory $logdetail
     * @param LoginFactory $loginlog
     * @param Data $helper
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        Session                $authSession,
        ManagerInterface       $messageManager,
        ObjectManagerInterface $objectManager,
        LoggerInterface        $logger,
        RequestInterface       $request,
        User                   $user,
        IpAdress               $ipAddress,
        PostDispatch           $postdispatch,
        ActionGridFactory      $actionLog,
        ActionDetailFactory    $logdetail,
        LoginFactory           $loginlog,
        Data                   $helper,
        CustomerRepository     $customerRepository
    ) {
        $this->authSession = $authSession;
        $this->messageManager = $messageManager;
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->request = $request;
        $this->user = $user;
        $this->ipAddress = $ipAddress;
        $this->postdispatch = $postdispatch;
        $this->actionLog = $actionLog;
        $this->logdetail = $logdetail;
        $this->loginlog = $loginlog;
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Init Action
     *
     * @param $fullActionName
     * @param $actionName
     * @return $this
     */
    public function initAction($fullActionName, $actionName)
    {
        $this->actionName = $actionName;

        if (isset($this->helper->getActionInfo()[$fullActionName])) {
            $this->action = $this->helper->getActionInfo()[$fullActionName];
            if (!$this->helper->getGroupActionAllow($this->action['group_name'])) {
                $this->action = null;
            }
        } elseif ($this->helper->addActionInfo($fullActionName)) {
            $this->action = $this->helper->addActionInfo($fullActionName)[$fullActionName];
            if (!$this->helper->getGroupActionAllow($this->action['group_name'])) {
                $this->action = null;
            }
        }

        if ($this->skipNextAction) {
            return $this;
        }

        $sessionValue = $this->authSession->getSkipLoggingAction();
        if ($fullActionName == $sessionValue) {
            $this->authSession->setSkipLoggingAction(null);
            $this->skipNextAction = true;

            return $this;
        }

        if (isset($this->action['skip_on_back'])) {
            $addValue = $this->action['skip_on_back'];
            $this->authSession->setSkipLoggingAction($addValue);
        }

        return $this;
    }

    /**
     * Model Action
     *
     * @param $model
     * @param $action
     * @return $this|bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function modelAction($model, $action)
    {
        if (!$this->action || $this->skipNextAction) {
            return false;
        }
        $eventGroupNode = $this->action;
        if (isset($eventGroupNode['expected_models'])) {
            if (is_array($eventGroupNode['expected_models'])) {
                $usedModels = $eventGroupNode['expected_models'];
            } else {
                if (get_class($model) == \Magento\Review\Model\Rating\Option::class) {
                    return false;
                }
                $usedModels = [$eventGroupNode['expected_models'] => []];
            }
        } else {
            return false;
        }

        $additionalData = $skipData = [];
        foreach ($usedModels as $className => $params) {
            if (!($model instanceof $className || str_contains(get_class($model), $className))) {
                return false;
            }

            $logDetail = $this->dataAction($className, $model, ucfirst($action), $eventGroupNode);

            if (!isset($eventGroupNode['post_dispatch'])) {
                $this->setInfo($className, $model);
            }

            if (!is_object($logDetail)) {
                return $this;
            }

            $logDetail->cleanupData();
            if ($logDetail->hasDifference()) {
                $this->addActionsDetail($logDetail);
            }
        }

        return $this;
    }

    /**
     * Get Original Data
     *
     * @param $model
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getOriginalData($model)
    {
        if ($model instanceof Customer) {
            return $this->customerRepository->getById($model->getId())->__toArray();
        }
        return $model->getOrigData();
    }

    /**
     * Get action
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Data Action
     *
     * @param $className
     * @param $model
     * @param $action
     * @param $eventGroupNode
     * @return bool|ActionDetail
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function dataAction($className, $model, $action, $eventGroupNode)
    {
        if ($action == 'View') {
            $logDetail = true;
        } elseif ($action == 'Delete') {
            $logDetail = $this->logdetail->create();
            if ($eventGroupNode['group_name'] == 'adminhtml_system_config') {
                $this->setInfoForSystemConfig($logDetail, $className, $model);
            } else {
                $logDetail->setSourceData($className)
                    ->setOldValue($this->getOriginalData($model))
                    ->setNewValue(null);
            }
        } else {
            $logDetail = $this->logdetail->create();
            if ($eventGroupNode['group_name'] == 'system_config') {
                $this->setInfoForSystemConfig($logDetail, $className, $model);
            } else {
                $oldValue = $this->getOriginalData($model);
                $newValue = $model->getData();

                if ($model instanceof Product) {
                    if (isset($model->getOrigData('quantity_and_stock_status')['qty'])) {
                        $oldValue['qty'] = $model->getOrigData('quantity_and_stock_status')['qty'];
                    }
                    $productData = $this->request->getParam('product');

                    if (isset($productData['quantity_and_stock_status']['qty'])) {
                        $newValue['qty'] = $productData['quantity_and_stock_status']['qty'];
                        $this->sendmail = true;
                    }
                }
                if (array_key_exists('update_time', $newValue)) {
                    $newValue['update_time'] = $newValue['update_time'] ?? '';
                    $newValue['update_time'] = $oldValue['update_time'] ?? $newValue['update_time'];
                }
                if (array_key_exists('hold_before_state', $newValue)
                    && array_key_exists('hold_before_status', $newValue)
                ) {
                    $newValue['hold_before_state'] = $newValue['hold_before_state'] ?? '';
                    $newValue['hold_before_state'] = $oldValue['hold_before_state'] ?? $newValue['hold_before_state'];
                    $newValue['hold_before_status'] = $newValue['hold_before_status'] ?? '';
                    $newValue['hold_before_status'] = $oldValue['hold_before_status'] ?? $newValue['hold_before_status'];
                }

                $logDetail->setSourceData($className)
                    ->setOldValue($oldValue)
                    ->setNewValue($newValue);
            }
        }

        return $logDetail;
    }

    /**
     * Set Info
     *
     * @param $className
     * @param $model
     * @return void
     */
    private function setInfo($className, $model)
    {
        if (!$this->name) {
            $this->name = $model->getName();
        }

        if (!$this->name) {
            $this->name = $model->getTitle();
        }

        if (!$this->name &&
            (
                $className == 'Magento\Sales\Model\Order'
                || $className == 'Magento\Sales\Model\Order\Invoice'
                || $className == 'Magento\Sales\Model\Order\Shipment'
                || $className == 'Magento\Sales\Model\Order\Creditmemo'
            )
        ) {
            $this->name = '#' . $model->getIncrementId();
        }

        if (!$this->name && $model->getId()) {
            $this->name = 'Id: ' . $model->getId();
        }
    }

    /**
     * Log Action
     *
     * @return $this|false
     */
    public function logAction()
    {
        if ($this->skipNextAction
            || $this->actionName == 'denied'
            || !$this->action
            || !$this->helper->isEnabled()
            || !$this->helper->getGroupActionAllow($this->action['group_name'])) {
            return false;
        }

        $logAction = $this->initLogAction();
        try {
            if (!$this->callback($logAction)) {
                return false;
            }

            if (!empty($logAction)) {
                $logAction->save();
                $this->saveActionLogDetails($logAction);
            }
        } catch (Exception $e) {
            $this->logger->critical($e);

            return false;
        }

        return $this;
    }

    /**
     * Add log with custom data
     *
     * @param array $data
     * @param array $dataDetail
     * @return bool
     */
    public function logCustomAction($data, $dataDetail)
    {
        if (!$this->helper->isEnabled()
            || !$this->helper->getGroupActionAllow($data['group_name'])
        ) {
            return false;
        }

        $userId = $this->authSession->isLoggedIn() ? $this->authSession->getUser()->getId() : null;
        $username = $this->authSession->isLoggedIn() ? $this->authSession->getUser()->getUsername() : null;
        $logAction = $this->actionLog->create()->setData(
            [
                'group_action' => $data['group_action'],
                'info' => $data['info'],
                'action_type' => $data['action_type'],
                'action_name' => $data['action_name'],
                'ip_address' => $this->ipAddress->getIpAdress(),
                'user_id' => $userId,
                'user_name' => $username,
                'result' => $data['result'],
                'store_id' => $data['store_id']
            ]
        );

        try {
            $logAction->save();
            if ($logAction->getId()) {
                $this->logCustomActionDetail($logAction, $dataDetail);
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
            return false;
        }

        return true;
    }

    /**
     * Add log details with custom data
     *
     * @param mixed $logAction
     * @param array $logDetails
     * @return true
     */
    public function logCustomActionDetail($logAction, $logDetails)
    {
        foreach ($logDetails as $detail) {
            $logDetail = $this->logdetail->create();
            $logDetail->setLogId($logAction->getId());
            $logDetail->setSourceData($detail['source_data']);
            $logDetail->setOldValue($detail['old_value']);
            $logDetail->setNewValue($detail['new_value']);
            $this->saveActionLogDetail($logDetail);
        }

        return true;
    }

    /**
     * Init Log Action
     *
     * @return ActionGrid
     */
    private function initLogAction()
    {
        $username = null;
        $userId = null;
        if ($this->authSession->isLoggedIn()) {
            $userId = $this->authSession->getUser()->getId();
            $username = $this->authSession->getUser()->getUsername();
        }

        $errors = $this->messageManager->getMessages()->getErrors();
        $actionType = $this->helper->getActionType();
        $storeId = $section = $this->request->getParam('store');
        if (!$storeId) {
            $storeId = 0;
        }
        if (!array_key_exists($this->action['controller_action'], $actionType)) {
            $actionType = $this->helper->getActionTypeFromFullAction($this->action['controller_action']);
        }
        $logAction = null;
        if ($this->action) {
            $logAction = $this->actionLog->create()->setData(
                [
                    'group_action' => $this->action['label'],
                    'info' => $this->name,
                    'action_type' => $actionType[$this->action['controller_action']],
                    'action_name' => $this->action['controller_action'],
                    'ip_address' => $this->ipAddress->getIpAdress(),
                    'user_id' => $userId,
                    'user_name' => $username,
                    'result' => empty($errors),
                    'store_id' => $storeId
                ]
            );
        }

        return $logAction;
    }

    /**
     * Log Admin Login
     *
     * @param $username
     * @param $status
     * @param null $userId
     * @return ActionGrid|void
     * @throws Exception
     */
    public function logAdminLogin($username, $status, $userId = null)
    {
        $this->loginlog->create()->logAdminLogin($username, $status, null, null);

        $eventCode = 'admin_login';
        if (!$this->helper->getGroupActionAllow($eventCode)) {
            return;
        }
        $actionType = $this->helper->getActionType();
        $storeId = $section = $this->request->getParam('store');
        if (!$storeId) {
            $storeId = 0;
        }
        $success = (bool)$userId;
        if (!$userId) {
            $userId = $this->user->loadByUsername($username)->getId();
        }

        $fullAction = $this->request->getRouteName() . '_' .
            $this->request->getControllerName() . '_' . $this->request->getActionName();

        $actionLog = $this->actionLog->create()->setData(
            [
                'group_action' => $eventCode,
                'info' => $username,
                'action_type' => 'login',
                'action_name' => $fullAction,
                'ip_address' => $this->ipAddress->getIpAdress(),
                'user_id' => $userId,
                'user_name' => $username,
                'result' => $success,
                'store_id' => $storeId
            ]
        );

        return $actionLog->save();
    }

    /**
     * Callback
     *
     * @param $logAction
     * @return $this|bool
     */
    private function callback($logAction)
    {
        $callback = 'Generic';

        if (isset($this->action['post_dispatch'])) {
            $callback = $this->action['post_dispatch'];
        }

        if (!$this->postdispatch->{$callback}($this->action, $logAction, $this)) {
            return false;
        }

        return $this;
    }

    /**
     * Save Action Log Details
     *
     * @param $logAction
     * @return $this|bool
     * @throws NoSuchEntityException
     * @throws MailException
     */
    public function saveActionLogDetails($logAction)
    {
        if (!$logAction->getId()) {
            return false;
        }

        foreach ($this->logDetails as $logDetail) {
            if ($logDetail && ($logDetail->getOldValue() || $logDetail->getNewValue())) {
                $logDetail->setLogId($logAction->getId());
                $this->saveActionLogDetail($logDetail);

                if ($this->sendmail) {
                    $this->helper->sendEmail($logDetail, $logAction);
                }
            }
        }

        return $this;
    }

    /**
     * Save Action Log Detail
     *
     * @param $logDetail
     * @return bool
     */
    public function saveActionLogDetail($logDetail)
    {
        try {
            $logDetail->save();
            return true;
        } catch (Exception $e) {
            $this->logger->critical($e);

            return false;
        }
    }

    /**
     * Add Actions Detail
     *
     * @param $logDetail
     * @return $this
     */
    public function addActionsDetail($logDetail)
    {
        $this->logDetails[] = $logDetail;

        return $this;
    }

    /**
     * Set Info For System Config
     *
     * @param $logDetail
     * @param $className
     * @param $model
     * @return void
     */
    public function setInfoForSystemConfig($logDetail, $className, $model)
    {
        $logDetail->setSourceData($className)
            ->setOldValue([$model->getPath() . '_scope_' . $model->getScope() . '_' .
            $model->getScopeId() => $model->getOldValue()])
            ->setNewValue([$model->getPath() . '_scope_' . $model->getScope() . '_' .
            $model->getScopeId() => $this->doRestoreToDefaultValue($model)]);
    }

    /**
     * Do Restore To Default Value
     *
     * @param $model
     * @return false|int|string
     */
    public function doRestoreToDefaultValue($model)
    {
        $fieldId = $model->getField();
        $modelValue = $model->getData();
        if (isset($model->getFieldConfig()['path'])) {
            $configPaths = explode('/', $model->getFieldConfig()['path']);
            unset($configPaths[0]);
            foreach ($configPaths as $configPath) {
                $modelValue = isset($modelValue['groups']) ? $modelValue['groups'][$configPath] : $modelValue;
            }
        }
        $modelValue = isset($modelValue['fields']) ? $modelValue['fields'][$fieldId] : $modelValue;
        if (isset($modelValue['inherit']) &&
            $modelValue['inherit'] == 1 &&
            $this->helper->getDefaultValue($model->getPath())) {
            return $this->helper->getDefaultValue($model->getPath());
        }
        return $model->getValue();
    }
}
