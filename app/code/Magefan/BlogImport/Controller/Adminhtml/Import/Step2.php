<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Controller\Adminhtml\Import;

/**
 * Blog prepare csv import controller
 */
class Step2 extends \Magento\Backend\App\Action
{
    /**
     * Prepare wordpress import
     *
     * @return void
     */
    public function execute(): void
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Magefan_BlogImport::import');
        $title = __('Step 2: Please associate columns');
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_addBreadcrumb($title, $title);

        $config = new \Magento\Framework\DataObject(
            (array)$this->_getSession()->getData('import_blogcsv_form_data', true) ?: []
        );

        $this->_objectManager->get(\Magento\Framework\Registry::class)->register('import_config', $config);

        $this->_view->renderLayout();
    }

    /**
     * Check is allowed access
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magefan_BlogImport::import');
    }
}
