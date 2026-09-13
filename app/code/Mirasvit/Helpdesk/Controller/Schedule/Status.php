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



namespace Mirasvit\Helpdesk\Controller\Schedule;

use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Helpdesk\Model\Config as Config;

class Status extends \Mirasvit\Helpdesk\Controller\Schedule
{
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        /** @var \Mirasvit\Helpdesk\Block\Contacts\Schedule\Status $child */
        $child = $this->context->getView()->getLayout()
            ->createBlock('Mirasvit\Helpdesk\Block\Contacts\Schedule\Status');
        $child->setTemplate('Mirasvit_Helpdesk::contacts/schedule/status.phtml');
        $child->setPage('contact-us');

        $resultPage->setData($child->_toHtml());

        return $resultPage;
    }
}
