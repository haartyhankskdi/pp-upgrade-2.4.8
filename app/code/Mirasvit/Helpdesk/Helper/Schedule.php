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

class Schedule
{
    /**
     * @var \Mirasvit\Helpdesk\Model\ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $context;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Locale\ListsInterface
     */
    protected $localeLists;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $workingDays = [];

    /**
     * @param \Mirasvit\Helpdesk\Model\Config                      $config
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Locale\ListsInterface             $localeLists
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Mirasvit\Helpdesk\Model\ScheduleFactory             $scheduleFactory
     * @param \Magento\Framework\App\Helper\Context                $context
     */
    /**
     * The schedule in force, per store, for this request; null is a real answer (no schedule matches
     * now), which is why the lookup uses array_key_exists. See getCurrentSchedule().
     *
     * @var array<int|string, \Mirasvit\Helpdesk\Model\Schedule|null> (PHP coerces a numeric-string key to an int)
     */
    private $currentScheduleMemo = [];

    public function __construct(
        \Mirasvit\Helpdesk\Model\Config $config,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Locale\ListsInterface $localeLists,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Mirasvit\Helpdesk\Model\ScheduleFactory $scheduleFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->config = $config;
        $this->localeDate = $localeDate;
        $this->localeLists = $localeLists;
        $this->storeManager = $storeManager;
        $this->scheduleFactory = $scheduleFactory;
        $this->context = $context;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Schedule|null
     */
    public function getCurrentSchedule()
    {
        $storeId = $this->storeManager->getStore()->getId();

        // ONE LOAD PER STORE PER REQUEST. Two blocks want this - Block\Contacts\Schedule and
        // Block\Contacts\Schedule\Status, each with its own template asking - so it loaded twice on
        // every contact page and every customer ticket page. Measured with the page profiler on a 2.4.7
        // store: two executions of this select with identical bound values on /contact, two more on
        // /helpdesk/ticket/.
        //
        // Nothing invalidates it, because nothing writes a schedule during a storefront request: the
        // admin grid saves them in a different request, and the answer is a function of the clock and
        // the store, both fixed for the request being served.
        if (array_key_exists((string)$storeId, $this->currentScheduleMemo)) {
            return $this->currentScheduleMemo[(string)$storeId];
        }

        /** @var \Mirasvit\Helpdesk\Model\ResourceModel\Schedule\Collection $collection */
        $collection = $this->scheduleFactory->create()->getCollection();
        $collection->addStoreFilter($storeId);
        $collection->addCurrentFilter();
        if ($collection->count()) {
            /** @var \Mirasvit\Helpdesk\Model\Schedule $schedule */
            $schedule = $collection->getLastItem();

            return $this->currentScheduleMemo[(string)$storeId] = $schedule;
        }

        return $this->currentScheduleMemo[(string)$storeId] = null;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\ResourceModel\Schedule\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUpcomingScheduleCollection()
    {
        $storeId = $this->storeManager->getStore()->getId();
        /** @var \Mirasvit\Helpdesk\Model\ResourceModel\Schedule\Collection $collection */
        $collection = $this->scheduleFactory->create()->getCollection();
        $collection->addStoreFilter($storeId);
        $collection->addCurrentScheduleFilter($this->getConfig()->getScheduleShowHolidayScheduleBeforeDays())
                    ->setOrder('sort_order', \Magento\Framework\Data\Collection::SORT_ORDER_DESC);
        if ($currentSchedule = $this->getCurrentSchedule()) {
            $collection->addFieldToFilter('schedule_id', ['neq' => $currentSchedule->getId()]);
        }
        return $collection;
    }

    /**
     * @return array
     */
    public function getWeekDays()
    {
        if (!$this->workingDays) {
            $this->workingDays = [];

            $days = $this->localeLists->getOptionWeekdays();
            foreach ($days as $day) {
                $this->workingDays[$day['value']] = $day['label'];
            }
        }

        return $this->workingDays;
    }
}
