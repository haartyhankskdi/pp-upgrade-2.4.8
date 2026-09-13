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



namespace Mirasvit\Helpdesk\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class AuthorizationType implements ArrayInterface
{
    const TYPE_IMAP = 'imap';
    const TYPE_OAUTH2 = 'oauth2';
    const TYPE_OAUTH2_MICROSOFT = 'oauth2_microsoft';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::TYPE_IMAP, 'label' => __('IMAP')],
            ['value' => self::TYPE_OAUTH2, 'label' => __('OAuth2 (Gmail)')],
            ['value' => self::TYPE_OAUTH2_MICROSOFT, 'label' => __('OAuth2 (Microsoft 365 / Exchange Online)')],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::TYPE_IMAP => __('IMAP'),
            self::TYPE_OAUTH2 => __('OAuth2 (Gmail)'),
            self::TYPE_OAUTH2_MICROSOFT => __('OAuth2 (Microsoft 365 / Exchange Online)'),
        ];
    }
}
