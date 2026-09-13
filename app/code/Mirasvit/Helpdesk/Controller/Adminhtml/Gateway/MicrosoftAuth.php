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



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Gateway;

/**
 * OAuth2 callback for Microsoft 365 / Exchange Online gateways.
 *
 * The callback flow (state/CSRF validation, code-for-token exchange, mailbox resolution)
 * is identical for every OAuth2 provider and lives in {@see GmailAuth}. This controller only
 * exists to give Microsoft its own callback route (.../gateway/microsoftAuth), so the redirect
 * URI shown in the gateway form and registered in the Azure App registration is self-describing
 * and independent of the Gmail one. The redirect URI actually used is resolved per gateway from
 * its authorization type (see \Mirasvit\Helpdesk\Helper\Oauth2Provider::getCallbackRoute), so the
 * inherited exchange logic already builds the correct microsoftAuth URI here.
 */
class MicrosoftAuth extends GmailAuth
{
}
