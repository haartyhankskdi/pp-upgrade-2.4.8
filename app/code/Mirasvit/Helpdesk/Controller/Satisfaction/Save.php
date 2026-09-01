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



namespace Mirasvit\Helpdesk\Controller\Satisfaction;

use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Helpdesk\Controller\Satisfaction;

class Save extends Satisfaction
{
    /**
     *
     */
    public function execute()
    {
        if (!$this->_request->isXmlHttpRequest()) {
            return;
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $rate    = $this->getRequest()->getParam('rate');
        $uid     = $this->getRequest()->getParam('uid');

        $url = $this->_url->getUrl('/');

        $satisfaction = $this->helpdeskSatisfaction->addRate($uid, $rate);
        if ($satisfaction) {
            $url = $this->_url->getUrl('helpdesk/satisfaction/form', ['uid' => $uid]);
        }

        $resultPage->setData(['url' => $url]);

        return $resultPage;
    }
}
