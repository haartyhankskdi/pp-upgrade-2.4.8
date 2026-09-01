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

use Magento\Framework\Model\AbstractModel;

class Visit extends AbstractModel
{
    /**
     * @var \Magento\Framework\View\Page\Title
     */
    protected $pageTitle;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var IpAdress
     */
    protected $ipAddress;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Bss\AdminActionLog\Helper\Data
     */
    protected $helper;

    /**
     * @var Login
     */
    protected $loginlog;

    /**
     * @var SessionActive
     */
    protected $sessionactive;

    /**
     * @var VisitDetail
     */
    protected $visitdetail;

    /**
     * @var ResourceModel\ClearLog
     */
    protected $clearlog;

    /**
     * Visit constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Page\Title $pageTitle
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Bss\AdminActionLog\Helper\Data $helper
     * @param IpAdress $ipAddress
     * @param Login $loginlog
     * @param SessionActive $sessionactive
     * @param VisitDetail $visitdetail
     * @param ResourceModel\ClearLog $clearlog
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Page\Title $pageTitle,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Bss\AdminActionLog\Helper\Data $helper,
        \Bss\AdminActionLog\Model\IpAdress $ipAddress,
        \Bss\AdminActionLog\Model\Login $loginlog,
        \Bss\AdminActionLog\Model\SessionActive $sessionactive,
        \Bss\AdminActionLog\Model\VisitDetail $visitdetail,
        \Bss\AdminActionLog\Model\ResourceModel\ClearLog $clearlog,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->pageTitle = $pageTitle;
        $this->urlInterface = $urlInterface;
        $this->authSession = $authSession;
        $this->customerSession = $customerSession;
        $this->dateTime = $dateTime;
        $this->helper = $helper;
        $this->ipAddress = $ipAddress;
        $this->loginlog = $loginlog;
        $this->sessionactive = $sessionactive;
        $this->visitdetail = $visitdetail;
        $this->clearlog = $clearlog;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bss\AdminActionLog\Model\ResourceModel\Visit');
    }

    /**
     * Process Visit Active
     *
     * @return $this|bool
     */
    public function processVisitActive()
    {
        if (!$this->helper->isEnabled()) {
            return false;
        }
        $this->checkOnline();
        if (!$this->helper->isAdminAccountSharingEnabled()) {
            $userName = $this->authSession->getUser()->getUserName();
            $sessionActives = $this->sessionactive->getCollection()
                                                    ->addFieldToFilter('user_name', $userName);
            if ($sessionActives->getSize()) {
                foreach ($sessionActives as $sessionActive) {
                    $this->loginlog
                        ->logAdminLogin($sessionActive->getUserName(), 3, $sessionActive->getIpAddress(), null);
                    $this->processVisitRemove($sessionActive->getSessionId(), true);
                }
            }
        }
        $this->saveSessionActive();
        $this->startVisit();
        return $this;
    }


    /**
     * Save Session Active
     *
     * @return void
     * @throws \Exception
     */
    protected function saveSessionActive()
    {
        $this->sessionactive->setData(
            [   'recent_activity' => $this->dateTime->gmtDate(),
                'session_id' => $this->authSession->getSessionId(),
                'user_name' => $this->authSession->getUser()->getUserName(),
                'name' => $this->authSession->getUser()->getName(),
                'ip_address' => $this->ipAddress->getIpAdress(),
                'created_at' => $this->dateTime->gmtDate()
            ]
        )->save();
    }


    /**
     * Start Visit
     *
     * @return void
     * @throws \Exception
     */
    protected function startVisit()
    {
        $this->setData(
            [
                    'user_name' => $this->authSession->getUser()->getUserName(),
                    'name' => $this->authSession->getUser()->getName(),
                    'ip_address' => $this->ipAddress->getIpAdress(),
                    'session_id' => $this->authSession->getSessionId(),
                    'session_start' => $this->dateTime->gmtDate(),
                    'session_end' => ''
                ]
        )->save();
    }

    /**
     * Process Visit Remove
     *
     * @param null $sessionId
     * @param bool $loginlog
     * @return bool|void
     * @throws \Exception
     */
    public function processVisitRemove($sessionId = null, $loginlog = false)
    {
        if (!$this->helper->isEnabled()) {
            return false;
        }

        if (!$loginlog && $this->authSession->getUser()) {
            $username = $this->authSession->getUser()->getUserName();
            $this->loginlog->logAdminLogin($username, 0, null, null);
        }

        if (!$sessionId) {
            $sessionId = $this->authSession->getSessionId();
        }

        $this->clearlog->deleteBySessionId($sessionId);

        $this->endVisit($sessionId);
    }

    /**
     * End Visit
     *
     * @param $sessionId
     */
    public function endVisit($sessionId)
    {
        $visit = $this->getCollection()->addFieldToFilter('session_id', $sessionId);
        if ($visit->getSize()) {
            $id = 0;
            foreach ($visit as $v) {
                $id = $v->getId();
                break;
            }
            $this->load($id)
                 ->setSessionEnd($this->dateTime->gmtDate())
                 ->save();
        }
        $this->saveLastPageDuration($sessionId);
    }

    /**
     * Get Last Session Page
     *
     * @param $sessionId
     * @return VisitDetail|bool
     */
    public function getLastSessionPage($sessionId)
    {
        $lastItem = $this->visitdetail->getCollection()
                                      ->addFieldToFilter('session_id', $sessionId);
        if ($lastItem->getSize()) {
            $i = 0;
            $id = 0;
            foreach ($lastItem as $v) {
                if (++$i === $lastItem->getSize()) {
                    $id = $v->getId();
                    break;
                }
            }
            return $this->visitdetail->load($id);
        }
        return false;
    }

    /**
     * Save Last Page Duration
     *
     * @param $sessionId
     * @throws \Exception
     */
    public function saveLastPageDuration($sessionId)
    {
        $lastPage = $this->getLastSessionPage($sessionId);
        if ($lastPage) {
            $lastPageData = $lastPage->getData();
            $time = time();

            $lastPageTime = $this->customerSession->getLastPageTime();

            if (!empty($lastPageData) && $lastPageTime) {
                $duration = $time - $lastPageTime;
                $lastPage->setStayDuration($duration);
                $lastPage->save();
            }
        }
    }


    /**
     * Update Online Admin Activity
     *
     * @return void
     * @throws \Exception
     */
    public function updateOnlineAdminActivity()
    {
        $sessionactive = $this->sessionactive->getCollection()
                                ->addFieldToFilter('session_id', $this->authSession->getSessionId());
        if ($sessionactive->getSize()) {
            $id = 0;
            foreach ($sessionactive as $v) {
                $id = $v->getId();
                break;
            }
            $this->sessionactive->load($id)
                                 ->setData('recent_activity', $this->dateTime->gmtDate())
                                 ->save();
        }
    }


    /**
     * Check Online
     *
     * @return void
     * @throws \Exception
     */
    public function checkOnline()
    {
        $collection = $this->sessionactive->getCollection();
        $sessionLifeTime = $this->helper->getAdminSessionLifetime();
        $currentTime = $this->dateTime->gmtTimestamp();

        foreach ($collection as $sessionActive) {
            $rowTime = strtotime($sessionActive->getRecentActivity());
            $timeDifference = $currentTime - $rowTime;
            if ($timeDifference >= $sessionLifeTime) {
                $timeLogout = $rowTime + $sessionLifeTime;
                $sessionId = $sessionActive->getSessionId();
                $this->loginlog->logAdminLogin($sessionActive->getUserName(), 2, null, $timeLogout);
                $this->processVisitRemove($sessionId, true);
            }
        }
    }


    /**
     * Save Detail Data Visit
     *
     * @return void
     * @throws \Exception
     */
    public function saveDetailDataVisit()
    {
        if ($this->pageTitle->getShort()) {
            $sessionId = $this->authSession->getSessionId();
            $visit = $this->getCollection()
                          ->addFieldToFilter('session_id', $sessionId);

            $detailData = [];

            if ($visit->getSize()) {
                $detailData['page_name'] = __($this->pageTitle->getShort());
                $detailData['page_url'] = $this->urlInterface->getCurrentUrl();
                $detailData['session_id'] = $sessionId;
                $this->saveLastPageDuration($sessionId);
                $this->customerSession->setLastPageTime(time());
                $this->visitdetail->setData($detailData)->save();
            }
        }
    }


    /**
     * Get Session Id
     *
     * @return array|mixed|null
     */
    public function getSessionId()
    {
        return $this->getData('session_id');
    }
}
