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

class EmailAddressAdapter
{
    private $address;
    private $name;

    public function __construct(string $raw, ?string $name = null)
    {
        // Parse RFC 5322 "Display Name <email@example.com>"
        if (preg_match('/^(.*?)\s*<([^>]+)>\s*$/', $raw, $matches)) {
            $this->address = trim($matches[2]);
            $this->name    = $name ?? (trim($matches[1]) ?: null);
        } else {
            $this->address = trim($raw);
            $this->name    = $name;
        }
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getName(): string
    {
        return $this->name ?? 'unknown';
    }

    public function __toString(): string
    {
        return $this->name ? $this->name . ' <' . $this->address . '>' : $this->address;
    }
}
