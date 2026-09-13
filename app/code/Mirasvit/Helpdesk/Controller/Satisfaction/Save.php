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
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->resultFactory->create(ResultFactory::TYPE_RAW);
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $rate      = $request->getParam('rate');
        $uid       = $request->getParam('uid');
        $userAgent = $request->getHeader('USER_AGENT') ? : '';
        $remoteIp  = $this->remoteAddress->getRemoteAddress();

        $url = $this->_url->getUrl('/');

        $satisfaction = $this->helpdeskSatisfaction->addRate($uid, $rate, $userAgent, $remoteIp);
        if ($satisfaction) {
            $url = $this->_url->getUrl('helpdesk/satisfaction/form', ['uid' => $uid]);
        }

        $resultPage->setData(['url' => $url]);

        return $resultPage;
    }
}
