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


// @codingStandardsIgnoreFile
// namespace Mirasvit_Ddeboer\Imap;

// use Mirasvit_Ddeboer\Imap\Exception\AuthenticationFailedException;

/**
 * An IMAP server.
 */
class Mirasvit_Ddeboer_Imap_Server
{
    /**
     * @var string Internet domain name or bracketed IP address of server
     */
    protected $hostname;

    /**
     * @var int TCP port number
     */
    protected $port;

    /**
     * @var string Optional flags
     */
    protected $flags;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Constructor.
     *
     * @param string $hostname Internet domain name or bracketed IP address of server
     * @param int    $port     TCP port number
     * @param string $flags    Optional flags
     */
    public function __construct($hostname, $port = 993, $flags = '/imap/ssl/validate-cert')
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->flags = '/'.ltrim($flags, '/');
    }

    /**
     * Authenticate connection.
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return Mirasvit_Ddeboer_Imap_Connection
     *
     */
    public function authenticate($username, $password)
    {
        $resource = @imap_open($this->getServerString(), $username, $password, 0, 1);

        if (false === $resource) {
            throw new Mirasvit_Ddeboer_Imap_Exception_AuthenticationFailedException($username);
        }

        $check = imap_check($resource);
        $mailbox = $check->Mailbox;
        $this->connection = substr($mailbox, 0, strpos($mailbox, '}') + 1);

        // These are necessary to get rid of PHP throwing IMAP errors
        imap_errors();
        imap_alerts();

        return new Mirasvit_Ddeboer_Imap_Connection($resource, $this->connection);
    }

    /**
     * Authenticate the connection using an OAuth2 access token (XOAUTH2 SASL).
     *
     * Used for providers that disable basic auth (Microsoft 365 / Exchange Online):
     * imap_open authenticates with the OAuth2 access token instead of a password,
     * via the OP_XOAUTH2 connection flag. OP_XOAUTH2 is available since PHP 7.2.
     *
     * @param string $username    Mailbox owner (authentication identity)
     * @param string $accessToken OAuth2 access token
     *
     * @return Mirasvit_Ddeboer_Imap_Connection
     */
    public function authenticateOAuth2($username, $accessToken)
    {
        $options = defined('OP_XOAUTH2') ? OP_XOAUTH2 : 0;

        $resource = @imap_open($this->getServerString(), $username, $accessToken, $options, 1);

        if (false === $resource) {
            // Capture the underlying IMAP error before it is cleared, so the real cause
            // (e.g. AUTHENTICATE failure, IMAP disabled, token audience) is not lost.
            $lastError = imap_last_error();
            $detail    = $username . ($lastError ? ' (' . $lastError . ')' : '');

            throw new Mirasvit_Ddeboer_Imap_Exception_AuthenticationFailedException($detail);
        }

        $check = imap_check($resource);
        $mailbox = $check->Mailbox;
        $this->connection = substr($mailbox, 0, strpos($mailbox, '}') + 1);

        // These are necessary to get rid of PHP throwing IMAP errors
        imap_errors();
        imap_alerts();

        return new Mirasvit_Ddeboer_Imap_Connection($resource, $this->connection);
    }

    /**
     * Glues hostname, port and flags and returns result.
     *
     * @return string
     */
    protected function getServerString()
    {
        return "{{$this->hostname}:{$this->port}{$this->flags}}";
    }
}
