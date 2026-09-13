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



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Pattern;

use Magento\Framework\Controller\ResultFactory;

class Add extends \Mirasvit\Helpdesk\Controller\Adminhtml\Pattern
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $this->_initPattern();

        $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('New Pattern'));
        $resultPage->addBreadcrumb(
            __('Pattern  Manager'),
            __('Pattern Manager'),
            $this->getUrl('*/*/')
        );
        $resultPage->addBreadcrumb(__('Add Pattern '), __('Add Pattern'));

        $resultPage->getLayout()
            ->getBlock('head')
            ;

        return $resultPage;
    }
}
