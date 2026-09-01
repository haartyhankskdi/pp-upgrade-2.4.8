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

class Browser
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * IpAdress constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get Browser used
     *
     * @return string
     */
    public function getBrowser()
    {
        $userAgent = $this->request->getServer('HTTP_USER_AGENT');
        $nameBrowser = 'Unknown';
        $version = "";

        // Next get the name of the useragent yes seperately and for good reason
        if (!isset($userAgent)) {
            $userAgent = "";
        }
        $codeBrower = "";
        if (preg_match('/MSIE/i', $userAgent) && !preg_match('/Opera/i', $userAgent)) {
            $nameBrowser = 'Internet Explorer';
            $codeBrower = "MSIE";
        } elseif (preg_match('/Edg/i', $userAgent)) {
            $nameBrowser = 'Microsoft Edge';
            $codeBrower = "Edg";
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $nameBrowser = 'Mozilla Firefox';
            $codeBrower = "Firefox";
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $nameBrowser = 'Google Chrome';
            $codeBrower = "Chrome";
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $nameBrowser = 'Apple Safari';
            $codeBrower = "Safari";
        } elseif (preg_match('/Opera/i', $userAgent)) {
            $nameBrowser = 'Opera';
            $codeBrower = "Opera";
        } elseif (preg_match('/Netscape/i', $userAgent)) {
            $nameBrowser = 'Netscape';
            $codeBrower = "Netscape";
        }

        // finally get the correct version number
        $known = ['Version', $codeBrower, 'other'];
        $pattern = '#(?<browser>' . join('|', $known) .
            ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $userAgent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);

        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($userAgent, "Version") < strripos($userAgent, $codeBrower)) {
                $version = $matches['version'][0];
            } else {
                $version = $matches['version'][1];
            }
        } else {
            $version = $matches['version'][0];
        }

        return $nameBrowser . " " . $version;
    }
}
