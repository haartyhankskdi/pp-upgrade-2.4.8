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



namespace Mirasvit\Helpdesk\Controller;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Mirasvit\Helpdesk\Helper\Satisfaction as HelpdeskSatisfaction;

abstract class Satisfaction extends Action
{
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Satisfaction
     */
    protected $helpdeskSatisfaction;

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    public function __construct(
        RemoteAddress $remoteAddress,
        HelpdeskSatisfaction $helpdeskSatisfaction,
        Context $context
    ) {
        $this->remoteAddress        = $remoteAddress;
        $this->helpdeskSatisfaction = $helpdeskSatisfaction;
        $this->context              = $context;
        $this->resultFactory        = $context->getResultFactory();

        parent::__construct($context);
    }
}
