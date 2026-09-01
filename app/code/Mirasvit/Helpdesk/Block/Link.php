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


namespace Mirasvit\Helpdesk\Block;

/**
 * Customer account dropdown link
 */
class Link extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    private $config;
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    /**
     * Link constructor.
     * @param \Mirasvit\Helpdesk\Model\Config $config
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\Config $config,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->config      = $config;
        $this->httpContext = $httpContext;
    }

    /**
     * @var string
     */
    protected $_template = 'Mirasvit_Helpdesk::link.phtml';

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl('helpdesk/ticket');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return $this->config->getDefaultFrontName($this->_storeManager->getStore());
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('helpdesk/ticket/getopen');
    }

    /**
     * @return bool
     */
    public function isShow()
    {
        return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH) &&
            $this->config->getGeneralShowInCustomerMenu();
    }
}
