<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Adminhtml\Card;

class Grid extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_card_grid';
        $this->_blockGroup = 'Magenest_SagePay';
        $this->_headerText = __('Card Identifiers');

        parent::_construct();

        if ($this->_isAllowedAction('Magenest_SagePay::save')) {
            $this->buttonList->update('add', 'label', __('Add New Card Identifier'));
        } else {
            $this->buttonList->remove('add');
        }
    }

    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
