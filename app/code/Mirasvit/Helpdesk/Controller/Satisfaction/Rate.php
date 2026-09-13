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



namespace Mirasvit\Helpdesk\Controller\Satisfaction;

use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Helpdesk\Controller\Satisfaction;

class Rate extends Satisfaction
{
    /**
     *
     */
    public function execute()
    {
        if (!$this->isAgentAllowed() || !$this->isIpAllowed()) {
            return $this->resultFactory->create(ResultFactory::TYPE_RAW);
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        return $resultPage;
    }

    /**
     * @return bool
     */
    private function isAgentAllowed()
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        $agent = (string) $request->getHeader('USER_AGENT');
        if (!$agent) {
            return false;
        }

        $agents = [
            'http://help.yahoo.com/help/us/ysearch/slurp',
            'Slurp',
            'Bot',
        ];

        foreach ($agents as $value) {
            if (stripos($agent, $value) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    private function isIpAllowed()
    {
        $remoteIp = $this->remoteAddress->getRemoteAddress();

        foreach($this->ipsToBlock() as $range) {
            if ($this->blockRangeIps($remoteIp, $range[0], $range[1])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @returns array
     */
    private function ipsToBlock() {
        return [
            ['51.140.0.0', '51.145.255.255'], //Microsoft Limited UK range ips // source https://ipinfo.io/AS8075/51.140.0.0/14
            ['51.116.0.0', '51.116.255.255'], //Microsoft Limited UK range ips // source https://ipinfo.io/AS8075/51.116.0.0/16
            ['40.74.0.0', '40.125.127.255'], // microsoft US range ips // source https://ipinfo.io/AS8075/40.74.0.0/15
            ['23.96.0.0', '23.103.255.255'], // microsoft US range ips // source https://ipinfo.io/AS8075/23.96.0.0/14
            ['20.33.0.0', '20.128.255.255'], // microsoft US range ips // source https://ipinfo.io/AS8075/20.64.0.0/10
            ['104.40.0.0', '104.47.255.255'], // microsoft US range ips // source https://ipinfo.io/AS8075/104.40.0.0/13
            ['20.192.0.0', '20.255.255.255'], // microsoft US range ips // source https://ipinfo.io/AS8075/20.192.0.0/10
            ['20.0.0.0', '20.31.255.255'], // microsoft US range ips // source https://ipinfo.io/AS8075/20.0.0.0/11
            ['51.10.0.0', '51.13.255.255'], // Microsoft Limited UK range ips // source https://ipinfo.io/AS8075/51.12.0.0/15
            ['34.248.0.0', '34.255.255.255'], // Amazon Data Services Ireland Limited range ips // source https://ipinfo.io/AS16509/34.248.0.0/13
            ['4.192.0.0', '4.207.255.255'], // Microsoft Corporation range ips // source https://ipinfo.io/4.198.68.131
            ['3.248.0.0', '3.255.255.255'], // Amazon Data Services Ireland Limited // source https://ipinfo.io/AS16509/3.248.0.0/13
            ['3.224.0.0', '3.239.255.255'], // Amazon Data Services NoVa // source https://ipinfo.io/AS14618/3.224.0.0/12
            ['34.192.0.0', '34.255.255.255'], // Amazon Technologies Inc // source https://ipinfo.io/AS14618/34.224.0.0/12
            ['54.90.0.0', '54.91.255.255'], // Amazon Data Services NoVa // source https://ipinfo.io/AS14618/54.90.0.0/15
            ['52.84.0.0', '52.95.255.255'], // Amazon Technologies Inc // source https://ipinfo.io/AS14618/52.90.0.0/15
            ['54.72.0.0', '54.73.255.255'], // Amazon.com, Inc. // source https://ipinfo.io/AS16509/54.72.0.0/16
            ['54.228.0.0', '54.229.255.255'], // Amazon.com, Inc. // source https://ipinfo.io/AS16509/54.228.0.0/16
            ['176.39.0.0', '176.39.255.255'], // Lanet Network Ltd // source https://ipinfo.io/AS39608/176.39.35.0/24
            ['108.59.0.0', '108.59.15.255'], // Leaseweb USA, Inc // source https://ipinfo.io/AS30633/108.59.0.0/20
            ['87.101.93.0', '87.101.93.255'], // M247 Miami Infrastructure // source https://ipinfo.io/AS9009/87.101.93.0/24
            ['46.63.7.205', '46.63.63.255'], // "X-City" Ltd // source https://ipinfo.io/AS51784/46.63.0.0/18
            ['38.205.188.0', '38.205.191.255'], // M247 Europe SRL // source https://ipinfo.io/38.205.189.227
            ['52.145.0.0', '52.191.255.255'], // Microsoft Corporation // source https://ipinfo.io/AS8075/52.160.0.0/11
            ['3.80.0.0', '3.95.255.255'], // Amazon Data Services NoVa // source https://ipinfo.io/AS14618/3.80.0.0/12
            ['44.192.0.0', '44.223.255.255'], // Amazon Data Services NoVa // source https://ipinfo.io/AS8075/52.160.0.0/11
            ['72.152.0.0', '72.155.255.255'], // Microsoft Corporation // source https://ipinfo.io/AS8075/72.152.0.0/14
            ['48.208.0.0', '48.208.255.255'], // Microsoft Routing, Peering, and DNS // source https://ipinfo.io/AS8075/48.208.0.0/13
            ['72.144.0.0', '72.147.255.255'], // Microsoft Corporation // source https://ipinfo.io/AS8075/72.144.0.0/14
            ['135.232.0.0', '135.232.255.255'], // Microsoft Corporation // source https://ipinfo.io/AS8075/135.232.0.0/14
        ];
    }

    /**
     * @returns bool
     */
    private function blockRangeIps($remoteAddr, $rangeStatrt, $rangeEnd) {
        $remoteAddr  = sprintf('%u', ip2long($remoteAddr));
        $rangeStatrt = sprintf('%u', ip2long($rangeStatrt));
        $rangeEnd    = sprintf('%u', ip2long($rangeEnd));

        if ($rangeStatrt <= $remoteAddr && $rangeEnd >= $remoteAddr) {
            return true;
        }

        return false;
    }


}
