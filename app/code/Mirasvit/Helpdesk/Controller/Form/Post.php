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



namespace Mirasvit\Helpdesk\Controller\Form;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Mirasvit\Helpdesk\Model\Config as Config;

class Post extends \Mirasvit\Helpdesk\Controller\Form
{
    /**
     * Create ticket from default magento contact form.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Exception
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $post = $this->getRequest()->getParams();
        if ($post) {
            try {
                $postObject = new \Magento\Framework\DataObject();
                $postObject->setData($post);

                if (trim($post['name']) == "") {
                    $this->messageManager->addErrorMessage(__('Field "Name" is required'));
                }

                if (trim($post['comment']) == "") {
                    $this->messageManager->addErrorMessage(__('Field "Comment" is required'));
                }

                $emailValidator = new \Magento\Framework\Validator\EmailAddress();
                if (!isset($post['mail']) || !$emailValidator->isValid(trim($post['mail']))) {
                    $this->messageManager->addErrorMessage(__('Incorrect email format.'));
                }

                if (trim($post['hideit']) != "") {
                    $this->messageManager->addErrorMessage(__('Something went wrong'));
                }

                if ($this->config->getGeneralContactUsIsActive()) {
                    //POST
                    //name
                    //email
                    //comment
                    //telephone
                    //priority_id
                    //department_id
                    $params = [];
                    $params['subject'] = $post['subject'];
                    $params['message'] = $post['comment'];
                    if ($phone = $post['telephone']) {
                        $params['message'] .= "\n".__('Telephone').': '.$phone;
                    }
                    if (isset($post['priority_id'])) {
                        $params['priority_id'] = $post['priority_id'];
                    }
                    if (isset($post['department_id'])) {
                        $params['department_id'] = $post['department_id'];
                    }

                    $params['customer_name'] = $post['name'];
                    $params['customer_email'] = $post['mail'];
                    $collection = $this->helpdeskField->getContactFormCollection();
                    foreach ($collection as $field) {
                        if (isset($post[$field->getCode()])) {
                            $params[$field->getCode()] = $post[$field->getCode()];
                        }
                    }
                    if (empty($post['email'])) { //spam protection
                        $this->helpdeskProcess->createFromPost($params, Config::CHANNEL_CONTACT_FORM);
                        $this->messageManager->addSuccessMessage(__(
                            'Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'
                        ));
                    } else {
                        $this->messageManager->addErrorMessage(__(
                            'Sorry, for technical reasons you can\'t submit your query from this browser. Please try using a different browser or computer.'
                        ));
                    }
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Unable to submit your request. Please, try again later'));
            }
        }
        $resultRedirect->setRefererUrl();

        return $resultRedirect;
    }
}
