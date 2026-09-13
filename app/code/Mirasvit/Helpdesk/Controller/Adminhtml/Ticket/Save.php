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
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Ticket;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Helper\Notification;
use Mirasvit\Helpdesk\Model\Config as Config;

class Save extends \Mirasvit\Helpdesk\Controller\Adminhtml\Ticket
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\Controller\Result\Json
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();

        if ($data = $request->getParams()) {
            if (!isset($data['customer_email']) && isset($data['store_id'])) {
                $params = ['store_id' => $data['store_id']];
                if (!empty($data['customer_id'])) {
                    $params['customer_id'] = $data['customer_id'];
                }
                $resultRedirect->setPath('*/*/add', $params);

                return $resultRedirect;
            }
            try {
                $data   = $this->prepareData($data);
                /** @var \Magento\User\Model\User $user */
                $user   = $this->context->getAuth()->getUser();
                $ticket = $this->helpdeskProcess->createOrUpdateFromBackendPost($data, $user);

                if (isset($data['reply']) && $data['reply'] != '' && $data['reply_type'] != Config::MESSAGE_INTERNAL) {
                    $this->messageManager->addSuccessMessage(__('Message was successfully sent'));
                } else {
                    $this->messageManager->addSuccessMessage(__('Ticket was successfully updated'));
                }

                $this->backendSession->setFormData(false);

                if (count($ticket->getCc()) > 5) {
                    $this->getMessageManager()->addWarningMessage(__('Field "Cc" is limited by %1 emails', Notification::LIMIT_MAX_CC));
                }

                if ($request->getParam('back')) {
                    $resultRedirect->setPath('*/*/edit', ['id' => $ticket->getId()]);
                    if ($request->isAjax()) {
                        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                            'success' => true,
                            'redirect' => $this->_url->getUrl('*/*/edit', ['id' => $ticket->getId()])
                        ]);
                    }
                    return $resultRedirect;
                }

                $resultRedirect->setPath('*/*/');
                if ($request->isAjax()) {
                    return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                        'success' => true,
                        'redirect' => $this->_url->getUrl('*/*/')
                    ]);
                }
                return $resultRedirect;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->backendSession->setFormData($data);
                if ($request->getParam('id')) {
                    $resultRedirect->setPath('*/*/edit', ['id' => $request->getParam('id')]);
                } else {
                    $resultRedirect->setPath('*/*/add');
                }

                return $resultRedirect;
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong while saving the ticket.')
                );
                $this->backendSession->setFormData($data);
                if ($request->getParam('id')) {
                    $resultRedirect->setPath('*/*/edit', ['id' => $request->getParam('id')]);
                } else {
                    $resultRedirect->setPath('*/*/add');
                }

                return $resultRedirect;
            }
        }
        $this->messageManager->addErrorMessage(__('Unable to find ticket to save'));
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }

    /**
     * @param array $data
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function prepareData($data)
    {
        if (!empty($data[TicketInterface::KEY_FP_REMIND_EMAIL])) {
            $emails = explode(',', $data[TicketInterface::KEY_FP_REMIND_EMAIL]);
            $emails = array_map('trim', $emails);

            $data[TicketInterface::KEY_FP_REMIND_EMAIL] = implode(',', $emails);
        }

        $emailValidator = new \Magento\Framework\Validator\EmailAddress();
        if (isset($data['customer_email']) && !$emailValidator->isValid(trim($data['customer_email']))) {
            throw new LocalizedException(__('Incorrect email format.'));
        }

        if (!empty($data['cc'])) {
            foreach (explode(',', trim($data['cc'])) as $email) {
                if (!$emailValidator->isValid(trim($email)))
                    throw new LocalizedException(__('Incorrect CC format.'));
            }
        }

        if (!empty($data['bcc'])) {
            foreach (explode(',', trim($data['bcc'])) as $email) {
                if (!$emailValidator->isValid(trim($email)))
                    throw new LocalizedException(__('Incorrect BCC format.'));
            }
        }

        return $data;
    }
}
