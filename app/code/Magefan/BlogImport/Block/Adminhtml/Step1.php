<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Block\Adminhtml;

use Magento\Store\Model\ScopeInterface;

/**
 * Step1 import block
 */
class Step1 extends \Magento\Backend\Block\Widget\Form\Container
{

    /**
     * Initialize wordpress import block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Magefan_BlogImport';
        $this->_controller = 'adminhtml';
        $this->_mode = 'step1';

        parent::_construct();

        if (!$this->_isAllowedAction('Magefan_BlogImport::import')) {
            $this->buttonList->remove('save');
        } else {
            $this->updateButton(
                'save',
                'label',
                __('Next')
            );
        }

        $this->buttonList->remove('delete');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Get form save URL
     *
     * @see getFormActionUrl()
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/step1Post', ['_current' => true]);
    }
}
