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



namespace Mirasvit\Helpdesk\Controller\Contact;

use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Helpdesk\Model\Config as Config;
use Mirasvit\Helpdesk\Controller\Contact;

class Postmessage extends Contact
{
    /**
     * Create ticket from Feedback form popup
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $fakeEmail = $this->getRequest()->getParam('email');

        if ($fakeEmail === '') { //email should not be null and should be empty
            $this->processHelper->createFromPost($this->getRequest()->getParams(), Config::CHANNEL_FEEDBACK_TAB);
            $this->messageManager->addSuccessMessage(
                __('Your request was successfully submitted. You should receive a confirmation email shortly.')
            );
        }

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
