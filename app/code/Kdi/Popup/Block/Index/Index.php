<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Kdi\Popup\Block\Index;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class Index extends Template
{
    const XML_PATH_ENABLED = 'kdi_productpopup/general/enabled';
    const XML_PATH_PRODUCT_ID = 'kdi_productpopup/general/product_id';
    const XML_PATH_TITLE = 'kdi_productpopup/general/title';
    const XML_PATH_MESSAGE = 'kdi_productpopup/general/message';

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function isEnabled(): bool
    {
        return (bool)$this->_scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function getProductId(): int
    {
        return (int)$this->_scopeConfig->getValue(self::XML_PATH_PRODUCT_ID, ScopeInterface::SCOPE_STORE);
    }

    public function getPopupTitle(): string
    {
        return (string)$this->_scopeConfig->getValue(self::XML_PATH_TITLE, ScopeInterface::SCOPE_STORE);
    }

    public function getPopupMessage(): string
    {
        return (string)$this->_scopeConfig->getValue(self::XML_PATH_MESSAGE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Cookie lifetime in seconds (24 hours).
     */
    public function getCookieLifetime(): int
    {
        return 86400;
    }

    public function getSubscribeUrl(): string
    {
        return $this->getUrl('popup/index/subscribe');
    }
}
