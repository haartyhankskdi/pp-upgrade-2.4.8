<?php

namespace Amasty\Reports\Block\Adminhtml\Report\Customers\Abandoned;

use Amasty\Base\Helper\Module;
use Magento\Backend\Block\Template;

class Information extends Template
{
    public const MODULE_NAME = 'Amasty_Reports';

    public const MARKETPLACE_ACART_URL = 'https://marketplace.magento.com/amasty-amcart.html';

    public const ACART_GUIDE_URL = 'https://amasty.com/abandoned-cart-email-for-magento-2.html'
    . '?utm_source=demo&utm_medium=gotopage&utm_campaign=reports-to-abandoned-cart';

    public const MARKETPLACE_REQUEST_QUOTE_URL = '';

    public const REQUEST_QUOTE_GUIDE_URL = 'https://amasty.com/request-a-quote-for-magento-2.html'
    . '?utm_source=demo&utm_medium=gotopage&utm_campaign=reports-to-request-quote';

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var Module
     */
    private $moduleHelper;

    public function __construct(
        \Magento\Framework\Module\Manager $moduleManager,
        Template\Context $context,
        Module $moduleHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleManager = $moduleManager;
        $this->moduleHelper = $moduleHelper;
    }

    /**
     * @inheritdoc
     */
    public function toHtml()
    {
        $html = '';
        if (!$this->isAcartEnabled() || !$this->isRequestQuoteEnabled()) {
            $html = parent::toHtml();
        }
        return $html;
    }

    /**
     * @return bool
     */
    public function isAcartEnabled()
    {
        return $this->moduleManager->isEnabled('Amasty_Acart');
    }

    /**
     * @return string
     */
    public function getAcartUrl()
    {
        $url = $this->moduleHelper->isOriginMarketplace(self::MODULE_NAME)
            ? self::MARKETPLACE_ACART_URL
            : self::ACART_GUIDE_URL;

        return $url;
    }

    /**
     * @return bool
     */
    public function isRequestQuoteEnabled()
    {
        return $this->moduleManager->isEnabled('Amasty_RequestQuote');
    }

    /**
     * @return string
     */
    public function getRequestQuoteUrl()
    {
        $url = $this->moduleHelper->isOriginMarketplace(self::MODULE_NAME)
            ? self::MARKETPLACE_REQUEST_QUOTE_URL
            : self::REQUEST_QUOTE_GUIDE_URL;

        return $url;
    }
}
