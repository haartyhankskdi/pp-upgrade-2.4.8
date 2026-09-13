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
 * A serialisable set of mail headers, shared by the API-based mailbox adapters (Gmail, Microsoft
 * Graph). The fetch pipeline's auto-response detection calls getHeaders()->toString(), so the
 * adapters return one of these.
 *
 * Named rather than an anonymous class on purpose: Magento 2.3's di:compile PhpScanner chokes on
 * `new class (...)` and aborts compilation, so the module must not use anonymous classes in code
 * that di:compile scans.
 */
class MailHeaderBag
{
    /**
     * @var array<string, string> header name => value
     */
    private $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return string RFC 822 header block
     */
    public function toString()
    {
        $string = '';
        foreach ($this->headers as $name => $value) {
            $string .= $name . ': ' . $value . "\r\n";
        }

        return $string;
    }
}
