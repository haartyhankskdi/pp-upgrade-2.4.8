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



namespace Mirasvit\Helpdesk\Model;

use Mirasvit\Helpdesk\Model\ResourceModel\Attachment\CollectionFactory as AttachmentCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\DesktopNotification\CollectionFactory as DesktopCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Draft\CollectionFactory as DraftCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory as TicketCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Gateway\CollectionFactory as GatewayCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Email\CollectionFactory as EmailCollectionFactory;
use Mirasvit\Helpdesk\Helper\Ruleevent;
use Mirasvit\Helpdesk\Helper\Followup;
use \Mirasvit\Helpdesk\Helper\Fetch;
use Mirasvit\Helpdesk\Helper\Email;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Cron
{
    const NOTIFICATION_DAYS = 30;

    const DRAFT_DAYS = 1;

    const EMAIL_DAYS = 366;

    private $attachmentCollectionFactory;

    private $desktopCollectionFactory;

    private $draftCollectionFactory;

    private $messageCollectionFactory;

    private $date;

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
        AttachmentCollectionFactory $attachmentCollectionFactory,
        DesktopCollectionFactory $desktopCollectionFactory,
        DraftCollectionFactory $draftCollectionFactory,
        MessageCollectionFactory $messageCollectionFactory,
        DateTime $date,
        GatewayFactory $gatewayFactory,
        TicketCollectionFactory $ticketCollectionFactory,
        GatewayCollectionFactory $gatewayCollectionFactory,
        EmailCollectionFactory $emailCollectionFactory,
        Config $config,
        Ruleevent $helpdeskRuleevent,
        Followup $helpdeskFollowup,
        Fetch $helpdeskFetch,
        Email $helpdeskEmail,
        Filesystem $filesystem,
        LoggerInterface $logger
    )
    {
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->desktopCollectionFactory    = $desktopCollectionFactory;
        $this->draftCollectionFactory      = $draftCollectionFactory;
        $this->messageCollectionFactory    = $messageCollectionFactory;
        $this->date                        = $date;
        $this->gatewayFactory              = $gatewayFactory;
        $this->ticketCollectionFactory     = $ticketCollectionFactory;
        $this->gatewayCollectionFactory    = $gatewayCollectionFactory;
        $this->emailCollectionFactory      = $emailCollectionFactory;
        $this->config                      = $config;
        $this->helpdeskRuleevent           = $helpdeskRuleevent;
        $this->helpdeskFollowup            = $helpdeskFollowup;
        $this->helpdeskFetch               = $helpdeskFetch;
        $this->helpdeskEmail               = $helpdeskEmail;
        $this->filesystem                  = $filesystem;
        $this->logger                      = $logger;
    }

    /**
     * @var string|null
     */
    private $lockFilePath = null;

    /**
     * @var resource|null
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

    public function magentoCronEveryHourRun() : void
    {
        $this->helpdeskRuleevent->newEventCheck(Config::RULE_EVENT_CRON_EVERY_HOUR);
    }

    public function magentoCronRun() : void
    {
        if ($this->getConfig()->getGeneralIsDefaultCron()) {
            $this->run();
        }
    }

    public function removeAttachments() : void
    {
        $days = $this->config->getGeneralAttachmentRemovalDays();

        if($this->config->getGeneralIsAllowAttachmentRemoval() && $days) {
            $date = $this->date->gmtDate('Y-m-d H:i', time() - 60 * 60 * 24 * $days);
            $messageCollection = $this->messageCollectionFactory->create();
            $messageIds = $messageCollection->addFieldToFilter('created_at', ["lt" => $date])->getColumnValues('message_id');
            $attachmentCollection = $this->attachmentCollectionFactory->create();
            $attachmentCollection->getSelect()->where('message_id in (?)', $messageIds);
            $countAttachment = $attachmentCollection->count();
            if ($countAttachment) {
                foreach ($attachmentCollection as $attachment) {
                    $attachment->delete();
                }
            }
            echo __('%1 attachments were deleted', $countAttachment);
        } else {
            echo ('Autoremoval is not configured in the module settings.');
        }
    }

    public function removeOutdated(): void
    {
        //Remove outdated desktop notifications
        $notificationDate = $this->date->gmtDate('Y-m-d H:i', time() - 60 * 60 * 24 * self::NOTIFICATION_DAYS);
        $desktopCollection = $this->desktopCollectionFactory->create();
        $messageIds = $desktopCollection->addFieldToFilter('created_at', ['lt' => $notificationDate])->getColumnValues('message_id');
        $desktopCollection->getSelect()->where('message_id in (?)', $messageIds);
        $this->removeEntities($desktopCollection, __('desktop notifications'));

        //Remove outdated drafts
        $draftDate = $this->date->gmtDate('Y-m-d H:i', time() - 60 * 60 * 24 * self::DRAFT_DAYS);
        $draftCollection = $this->draftCollectionFactory->create();
        $draftCollection->addFieldToFilter('updated_at', ['lt' => $draftDate]);
        $this->removeEntities($draftCollection, 'drafts');

        //Remove outdated emails
        $emailDate = $this->date->gmtDate('Y-m-d H:i', time() - 60 * 60 * 24 * self::EMAIL_DAYS);
        $emailCollection = $this->emailCollectionFactory->create();
        $emailCollection->addFieldToFilter('created_at', ['lt' => $emailDate]);
        $this->removeEntities($emailCollection, 'emails');
    }

    public function removeEntities($collection, $entity): void
    {
        $countMessages = $collection->count();

        if ($countMessages) {
            foreach ($collection as $entity) {
                $entity->delete();
            }
        }

        echo __('%1 %2 were deleted', $countMessages, $entity);
    }

    public function shellCronRun($output = null) : void
    {
        $this->output = $output;
        $this->run();
    }

    public function setFast($fast) : void
    {
        $this->_fast = $fast;
    }

    public function run() : void
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
    public function updateGatewaysMessage() : void
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

    public function runFollowUp() : void
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

    public function fetchEmails() : void
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

    public function processEmails() : void
    {
        $emails = $this->emailCollectionFactory->create()
            ->addFieldToFilter('is_processed', false);
        foreach ($emails as $email) {
            $this->helpdeskEmail->processEmail($email);
        }
    }

    /**
     * @return resource
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function _getLockFile()
    {
        if ($this->_lockFile === null) {
            $file = $this->getFilePath();
            if (is_file($file)) {
                $lockFile = fopen($file, 'w');
            } else {
                $lockFile = fopen($file, 'x');
            }
            /** @var resource $lockFile */
            $this->_lockFile = $lockFile;
            fwrite($lockFile, date('r'));
        }

        return $this->_lockFile;
    }

    private function getFilePath() : string
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

        return (string)$this->lockFilePath;
    }

    /**
     * Lock file. File will unlock if process was terminated
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function lock() : object
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
    public function lockAndBlock() : object
    {
        flock($this->_getLockFile(), LOCK_EX);

        return $this;
    }

    public function unlock() : object
    {
        flock($this->_getLockFile(), LOCK_UN);

        return $this;
    }

    public function forceUnlock() : object
    {
        $fp = $this->_getLockFile();
        flock($fp, LOCK_UN);
        fclose($fp);
        $this->_lockFile = null;
        unlink($this->getFilePath());

        return $this;
    }

    public function isLocked() : bool
    {
        $fp = $this->_getLockFile();
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            flock($fp, LOCK_UN);

            return false;
        }

        return true;
    }

    public function __destruct()
    {
        if ($this->_lockFile) {
            fclose($this->_lockFile);
        }
    }
}
