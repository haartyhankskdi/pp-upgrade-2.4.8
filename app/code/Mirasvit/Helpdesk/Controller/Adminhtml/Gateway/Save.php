<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Gateway;

use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Helpdesk\Api\Data\GatewayInterface;

class Save extends \Mirasvit\Helpdesk\Controller\Adminhtml\Gateway
{
    const PASSWORD_PLACEHOLDER = '*****';

    /**
     * Save gateway.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($data = $this->getRequest()->getParams()) {
            $gateway = $this->_initGateway();
            $gateway->addData($this->prepareData($data));
            if (isset($data['password']) && $data['password'] != self::PASSWORD_PLACEHOLDER) {
                $gateway->setPassword($data['password']);
            }
            try {
                $gateway->save();
                $fetchHelper = $this->helpdeskFetch;
                if ($fetchHelper->connect($gateway)) {
                    $this->messageManager->addSuccess(__('Gateway was successfully saved. Connection has been established.'));
                    $fetchHelper->close();
                }
                $this->backendSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $gateway->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Mirasvit_Ddeboer_Imap_Exception_AuthenticationFailedException $e) {
                $message = $e->getMessage();
                $message .= ' ('.$this->helpdeskCheckenv->checkGateway($gateway).')';
                $this->messageManager->addErrorMessage($message);
                $this->backendSession->setFormData($data);

                $id = $this->getRequest()->getParam('id') ?: $gateway->getId();

                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->backendSession->setFormData($data);

                if ($this->getRequest()->getParam('id')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                } else {
                    return $resultRedirect->setPath('*/*/add');
                }
            }
        }
        $this->messageManager->addError(__('Unable to find Gateway to save'));

        return $resultRedirect->setPath('*/*/');
    }
}
