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

/**
 * A single mail recipient address, shared by the API-based mailbox adapters (Gmail, Microsoft
 * Graph). Named rather than an anonymous class on purpose: Magento 2.3's di:compile PhpScanner
 * chokes on `new class (...)` and aborts compilation, so the module must not use anonymous classes
 * in code that di:compile scans.
 *
 * Exposes both getAddress() and public mailbox/host so it satisfies every call site in the fetch
 * pipeline (Fetch::getTo() reads getAddress(); Fetch::getCc() reads mailbox/host).
 */
class MailRecipient
{
    /**
     * @var string
     */
    public $mailbox;

    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    private $address;

    /**
     * @param string $address
     */
    public function __construct($address)
    {
        $this->address = (string)$address;

        $parts         = explode('@', $this->address);
        $this->mailbox = $parts[0];
        $this->host    = $parts[1] ?? '';
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }
}
