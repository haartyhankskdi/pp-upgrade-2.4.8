<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Webkul\AbandonedCart\Model\Quote\Item;

use Magento\Quote\Model\Quote\Item;
use Magento\Framework\Api\AttributeValueFactory;

class AbstractItem extends \Magento\Framework\Model\AbstractExtensibleModel 
{
    public function aroundCheckData(
        \Magento\Quote\Model\Quote\Item\AbstractItem $subject,
        callable $proceed
    )
    {
        $this->setHasError(false);
        $this->clearMessage();

        $qty = $this->_getData('qty');

        try {
            $this->setQty($qty);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->setHasError(true);
            $this->setMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->setHasError(true);
            $this->setMessage(__('Item qty declaration error'));
        }
        
        return $this;
    }

     /**
     * Clears all messages
     *
     * @return $this
     */
    public function clearMessage()
    {
        $this->unsMessage();
        // For older compatibility, when we kept message inside data array
        $this->_messages = [];
        return $this;
    }
}
