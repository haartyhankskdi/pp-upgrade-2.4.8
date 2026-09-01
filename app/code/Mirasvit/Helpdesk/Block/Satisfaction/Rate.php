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



namespace Mirasvit\Helpdesk\Block\Satisfaction;

use Magento\Framework\View\Element\Template;
use Mirasvit\Core\Service\SerializeService as Serializer;

class Rate extends Template
{
    /**
     * {@inheritDoc}
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->pageConfig->getTitle()->set(__('Processing Rate'));
    }

    /**
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->_urlBuilder->getUrl('helpdesk/satisfaction/save');
    }

    /**
     * @return string
     */
    public function getSubmitData()
    {
        $rate = (int)$this->getRequest()->getParam('rate');
        $uid  = $this->getRequest()->getParam('uid');

        return Serializer::encode([
            'uid'  => $uid,
            'rate' => $rate
        ]);
    }
}
