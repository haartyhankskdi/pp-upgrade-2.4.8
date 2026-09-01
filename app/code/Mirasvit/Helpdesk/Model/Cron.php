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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Model;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Cron
{
    private $gatewayFactory;

    private $ticketCollectionFactory;

    private $gatewayCollectionFactory;

    private $emailCollectionFactory;

    private $config;

    private $helpdeskRuleevent;

    private $helpdeskFollowup;

    private $helpdeskFetch;

    private $helpdeskEmail;

    private $filesystem;

    private $logger;

    private $output;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        GatewayFactory $gatewayFactory,
        ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory,
        ResourceModel\Gateway\CollectionFactory $gatewayCollectionFactory,
        ResourceModel\Email\CollectionFactory $emailCollectionFactory,
        Config $config,
        \Mirasvit\Helpdesk\Helper\Ruleevent $helpdeskRuleevent,
        \Mirasvit\Helpdesk\Helper\Followup $helpdeskFollowup,
        \Mirasvit\Helpdesk\Helper\Fetch $helpdeskFetch,
        \Mirasvit\Helpdesk\Helper\Email $helpdeskEmail,
        \Magento\Framework\Filesystem $filesystem,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->gatewayFactory           = $gatewayFactory;
        $this->ticketCollectionFactory  = $ticketCollectionFactory;
        $this->gatewayCollectionFactory = $gatewayCollectionFactory;
        $this->emailCollectionFactory   = $emailCollectionFactory;
        $this->config                   = $config;
        $this->helpdeskRuleevent        = $helpdeskRuleevent;
        $this->helpdeskFollowup         = $helpdeskFollowup;
        $this->helpdeskFetch            = $helpdeskFetch;
        $this->helpdeskEmail            = $helpdeskEmail;
        $this->filesystem               = $filesystem;
        $this->logger                   = $logger;
    }

    /**
     * @var null
     */
    private $lockFilePath = null;

    /**
     * @var null
     */
    protected $_lockFile = null;

    /**
     * @var bool
     */
    protected $_fast = false;

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     *
     */
    public function magentoCronEveryHourRun()
    {
        $this->helpdeskRuleevent->newEventCheck(Config::RULE_EVENT_CRON_EVERY_HOUR);
    }

    /**
     *
     */
    public function magentoCronRun()
    {
        if ($this->getConfig()->getGeneralIsDefaultCron()) {
            $this->run();
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    public function shellCronRun($output = null)
    {
        $this->output = $output;
        $this->run();
    }

    /**
     * @param bool $fast
     *
     * @return void
     */
    public function setFast($fast)
    {
        $this->_fast = $fast;
    }

    /**
     *
     */
    public function run()
    {
        @set_time_limit(60 * 30); //30 min. we need this. otherwise script can hang out.
        if (!$this->isLocked() || $this->_fast) {
            $this->lock();

            $this->fetchEmails();
            $this->processEmails();
            $this->runFollowUp();

            $this->unlock();
        } else {
            if ($this->output) {
                $this->output->writeln('Process is locked');
            } else {
                $this->logger->info(__('Process is locked'));
            }
            $this->updateGatewaysMessage();
        }
    }

    /**
     * @throws \Exception
     */
    public function updateGatewaysMessage()
    {
        $isLocked = $this->isLocked();
        $gateways = $this->gatewayCollectionFactory->create()
            ->addFieldToFilter('is_active', true);
        foreach ($gateways as $gateway) {
            $timeNow = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            if (!$this->_fast) {
                $fetchTries     = 3;
                $fetchPeriod    = strtotime($timeNow) - strtotime($gateway->getFetchedAt());
                $fetchFrequency = $gateway->getFetchFrequency() * 60 * $fetchTries;
                if ($fetchPeriod > $fetchFrequency && $isLocked) {
                    $message = __('Locked');
                } else {
                    $message = __('Success');
                }
                // gateway can change its data, so we should reload it
                $gateway = $this->gatewayFactory->create()->load($gateway->getId());
                $gateway->setLastFetchResult($message)
                    ->setFetchedAt($timeNow)
                    ->save();
            }
        }
    }

    /**
     *
     */
    public function runFollowUp()
    {
        $collection = $this->ticketCollectionFactory->create()
            ->addFieldToFilter(
                'fp_execute_at',
                ['lteq' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)]
            );
        foreach ($collection as $ticket) {
            $this->helpdeskFollowup->process($ticket);
        }
    }

    /**
     *
     */
    public function fetchEmails()
    {
        $gateways = $this->gatewayCollectionFactory->create()
            ->addFieldToFilter('is_active', true);
        foreach ($gateways as $gateway) {
            $timeNow = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            if (!$this->_fast) {
                if (strtotime($timeNow) - strtotime($gateway->getFetchedAt()) < $gateway->getFetchFrequency() * 60) {
                    continue;
                }
            }
            $message = __('Success');
            try {
                $this->helpdeskFetch->fetch($gateway);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $this->logger->error("Can't connect to gateway {$gateway->getName()}. " . $e->getMessage());
            }
            // gateway can change its data, so we should reload it
            $gateway = $this->gatewayFactory->create()->load($gateway->getId());
            $gateway->setLastFetchResult($message)
                ->setFetchedAt($timeNow)
                ->save();
        }
    }

    /**
     *
     */
    public function processEmails()
    {
        $emails = $this->emailCollectionFactory->create()
            ->addFieldToFilter('is_processed', false);
        foreach ($emails as $email) {
            $this->helpdeskEmail->processEmail($email);
        }
    }

    /**
     * @return bool|resource|null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function _getLockFile()
    {
        if ($this->_lockFile === null) {
            $file = $this->getFilePath();
            if (is_file($file)) {
                $this->_lockFile = fopen($file, 'w');
            } else {
                $this->_lockFile = fopen($file, 'x');
            }
            fwrite($this->_lockFile, date('r'));
        }

        return $this->_lockFile;
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getFilePath()
    {
        if ($this->lockFilePath === null) {
            $varDir = $this->filesystem
                ->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::TMP)
                ->getAbsolutePath();
            if (!file_exists($varDir)) {
                @mkdir($varDir, 0777, true);
            }
            $this->lockFilePath = $varDir . '/helpdesk.lock';
        }

        return $this->lockFilePath;
    }

    /**
     * Lock file. File will unlock if process was terminated
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function lock()
    {
        flock($this->_getLockFile(), LOCK_EX | LOCK_NB);

        return $this;
    }

    /**
     * Lock and block process.
     * If new instance of the process will try validate locking state
     * script will wait until process will be unlocked.
     * @return Cron
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function lockAndBlock()
    {
        flock($this->_getLockFile(), LOCK_EX);

        return $this;
    }

    /**
     * Unlock file
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function unlock()
    {
        flock($this->_getLockFile(), LOCK_UN);

        return $this;
    }

    /**
     * Unlock file
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function forceUnlock()
    {
        $fp = $this->_getLockFile();
        flock($fp, LOCK_UN);
        fclose($fp);
        $this->_lockFile = null;
        unlink($this->getFilePath());

        return $this;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function isLocked()
    {
        $fp = $this->_getLockFile();
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            flock($fp, LOCK_UN);

            return false;
        }

        return true;
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->_lockFile) {
            fclose($this->_lockFile);
        }
    }
}
