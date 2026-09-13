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



namespace Mirasvit\Helpdesk\Plugin;

use Mirasvit\Helpdesk\Model\ResourceModel\Gateway\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Mirasvit\Helpdesk\Model\Config;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mirasvit\Core\Helper\Cron;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Url;

class CheckCronStatus
{
    protected $httpRequest;

    protected $gatewayCollectionFactory;

    protected $config;

    protected $date;

    protected $mstcoreCron;

    protected $backendUrlManager;

    protected $messageManager;

    protected $auth;

    protected $cronChecked = false;

    public function __construct(
        Http              $httpRequest,
        CollectionFactory $gatewayCollectionFactory,
        Config            $config,
        DateTime          $date,
        Cron              $mstcoreCron,
        ManagerInterface  $messageManager,
        Auth              $auth,
        Url               $backendUrlManager
    )
    {
        $this->httpRequest              = $httpRequest;
        $this->gatewayCollectionFactory = $gatewayCollectionFactory;
        $this->config                   = $config;
        $this->date                     = $date;
        $this->mstcoreCron              = $mstcoreCron;
        $this->messageManager           = $messageManager;
        $this->auth                     = $auth;
        $this->backendUrlManager        = $backendUrlManager;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function beforeDispatch($subject, RequestInterface $request)
    {
        $admin = $this->auth->getUser();
        if (!$admin
            || $this->cronChecked
            || $this->httpRequest->isAjax()) {
            return $this;
        }

        try {
            $gateways = $this->gatewayCollectionFactory->create()
                ->addFieldToFilter('is_active', true);
            $gatewayNames = '';
            foreach ($gateways as $gateway) {
                if ($gateway->getLastFetchResult()
                    && strpos($gateway->getLastFetchResult(), 'Authentication failed') !== false)
                {
                    $gatewayNames .= $gateway->getName() . ' ';
                }
            }
            if ($gatewayNames) {
                $this->messageManager->addError(
                    __('Authentication failed for the Gateway(s) "%1"
                                <br> To hide this message, re-save the above Gateway(s) and run cron,
                                or disable the above Gateway(s) <a href="%2">help desk gateways</a>.',
                       $gatewayNames,
                       $this->backendUrlManager->getUrl('helpdesk/gateway')
                    ));
                $this->cronChecked = true;

                return $this;
            }
            $gateways = $this->gatewayCollectionFactory->create()
                ->addFieldToFilter('is_active', true)
                ->addFieldToFilter(
                    'fetched_at',
                    ['lt' => $this->date->gmtDate(
                        null,
                        $this->date->timestamp() - 60 * 60 * 3
                        )
                    ]
                );

            if ($gateways->count() == 0) {

                return $this;
            }
        } catch (\Exception $e) {
            // intentional no-op: tables may not exist yet (e.g. before setup:upgrade); skip the cron check
            return $this;
        }
        //if we here, then something wrong. we have not fetched emails during long time.

        if ($this->config->getGeneralIsDefaultCron()) {
            list($result, $message) = $this->mstcoreCron->checkCronStatus('mirasvit_helpdesk', false);
            if ($result) {
                $message = __(
                    'The Help Desk can\'t fetch new emails.
                Please, try to run bin/magento mirasvit:helpdesk:run to determine the source of this problem.'
                );
            } else {
                $message = __('Help desk can\'t fetch emails. ') . $message;
                $message .= __(
                    '<br> To temporarily hide this message, disable all <a href="%1">help desk gateways</a>.',
                    $this->backendUrlManager->getUrl('helpdesk/gateway')
                );
            }
        } else {
            $message = __(
                'Help Desk can\'t fetch new emails.
                 Please, check that you are running cron for bin/magento mirasvit:helpdesk:run.'
            );
        }
        $this->messageManager->addError($message);
        $this->cronChecked = true;

        return $this;
    }
}
