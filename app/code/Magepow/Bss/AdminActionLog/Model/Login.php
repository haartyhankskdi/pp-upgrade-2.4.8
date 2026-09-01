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

use Magento\Framework\Model\AbstractModel;

class Login extends AbstractModel
{
    /**
     * @var IpAdress
     */
    protected $ipAddress;

    /**
     * @var \Bss\AdminActionLog\Helper\Data
     */
    protected $helper;

    /**
     * @var Browser
     */
    protected $browser;

    /**
     * Login constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Bss\AdminActionLog\Helper\Data $helper
     * @param Browser $browser
     * @param IpAdress $ipAddress
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Bss\AdminActionLog\Helper\Data                         $helper,
        \Bss\AdminActionLog\Model\Browser                       $browser,
        \Bss\AdminActionLog\Model\IpAdress                      $ipAddress,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->helper = $helper;
        $this->browser = $browser;
        $this->ipAddress = $ipAddress;
    }


    /**
     * construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bss\AdminActionLog\Model\ResourceModel\Login');
    }

    /**
     * Log Admin Login
     *
     * @param $username
     * @param $status
     * @param $ipAddress
     * @param $created_at
     * @return bool|void
     * @throws \Exception
     */
    public function logAdminLogin($username, $status, $ipAddress, $created_at)
    {
        if (!$this->helper->isEnabled()) {
            return false;
        }

        if (!$ipAddress) {
            $ipAddress = $this->ipAddress->getIpAdress();
        }

        $browser = $this->browser->getBrowser();
        if ($created_at) {
            $this->setData(['created_at' => $created_at]);
        }

        $this->setData(
            [
                'ip_address' => $ipAddress,
                'user_name' => $username,
                'status' => $status,
                'browser' => $browser
            ]
        );

        $this->save();
    }
}
