<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AdminActionLog
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AdminActionLog\Model;

class IpAdress
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Ip Address constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get Ip Address
     *
     * @return string
     */
    public function getIpAdress()
    {
        $ipAddress = '';
        if ($this->request->getServer('HTTP_CLIENT_IP')) {
            $ipAddress = $this->request->getServer('HTTP_CLIENT_IP');
        } elseif ($this->request->getServer('HTTP_X_FORWARDED_FOR')) {
            $ipAddress = $this->request->getServer('HTTP_X_FORWARDED_FOR');
        } elseif ($this->request->getServer('HTTP_X_FORWARDED')) {
            $ipAddress = $this->request->getServer('HTTP_X_FORWARDED');
        } elseif ($this->request->getServer('HTTP_FORWARDED_FOR')) {
            $ipAddress = $this->request->getServer('HTTP_FORWARDED_FOR');
        } elseif ($this->request->getServer('HTTP_FORWARDED')) {
            $ipAddress = $this->request->getServer('HTTP_FORWARDED');
        } elseif ($this->request->getServer('REMOTE_ADDR')) {
            $ipAddress = $this->request->getServer('REMOTE_ADDR');
        } else {
            $ipAddress = 'UNKNOWN';
        }

        return $ipAddress;
    }
}
