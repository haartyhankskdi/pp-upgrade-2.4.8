<?php

namespace Amasty\Reports\Model\Abandoned;

use Amasty\Reports\Model\ResourceModel\Abandoned\Cart as ResourceCart;
use Magento\Framework\Model\AbstractModel;

class Cart extends AbstractModel
{
    public function _construct()
    {
        $this->_init(ResourceCart::class);
    }

    /**
     * @param int $quoteId
     */
    public function loadByQuoteId($quoteId)
    {
        // phpcs:ignore Magento2.Methods.DeprecatedModelMethod.FoundDeprecatedModelMethod
        $this->getResource()->load($this, $quoteId, ResourceCart::QUOTE_ID);
    }
}
