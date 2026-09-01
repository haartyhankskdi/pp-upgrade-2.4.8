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


namespace Mirasvit\Helpdesk\Block\Account;

class Link extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    private $config;

    /**
     * Link constructor.
     * @param \Mirasvit\Helpdesk\Model\Config $config
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param array $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\Config $config,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->config = $config;
    }

    /**
     * @return object
     */
    public function getLabel()
    {
        return $this->config->getDefaultFrontName($this->_storeManager->getStore());
    }
}
