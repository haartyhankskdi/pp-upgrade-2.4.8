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



namespace Mirasvit\Helpdesk\Helper;

class Checkenv extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $context;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->context = $context;
        parent::__construct($context);
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Gateway $gateway
     *
     * @return string
     */
    public function checkGateway($gateway)
    {
        $result = [];
        $ports = ['gmail.com' => 80, $gateway->getHost() => $gateway->getPort()];
        foreach ($ports as $host => $port) {
            $connection = @fsockopen($host, $port);
            if (is_resource($connection)) {
                $result[] = __('%1:%2 (%3) is open.', $host, $port, getservbyport($port, 'tcp'));
                fclose($connection);
            } else {
                $result[] = __('%1:%2 is closed.', $host, $port);
            }
        }

        return implode("\n; ", $result);
    }
}
