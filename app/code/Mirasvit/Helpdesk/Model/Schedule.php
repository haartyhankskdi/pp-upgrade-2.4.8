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

use Magento\Framework\DataObject;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * @method \Mirasvit\Helpdesk\Model\Schedule setName(string $name)
 * @method string getName()
 * @method \Mirasvit\Helpdesk\Model\Schedule setIsActive(bool $param)
 * @method bool getIsActive()
 * @method \Mirasvit\Helpdesk\Model\Schedule setActiveFrom(string $param)
 * @method \Mirasvit\Helpdesk\Model\Schedule setActiveTo(string $param)
 * @method \Mirasvit\Helpdesk\Model\Schedule setTimezone(string $param)
 * @method string getTimezone()
 * @method \Mirasvit\Helpdesk\Model\Schedule setSortOrder(int $param)
 * @method int getSortOrder()
 * @method \Mirasvit\Helpdesk\Model\Schedule setIsHoliday(bool $param)
 * @method bool getIsHoliday()
 * @method int getType()
 * @method $this setType(int $param)
 * @method string getClosedMessage()
 * @method $this setClosedMessage(string $param)
 *
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Schedule extends \Magento\Framework\Model\AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'helpdesk_schedule';
    /**
     * @var string
     */
    protected $_cacheTag = 'helpdesk_schedule';

    /**
     * @var string
     */
    protected $_eventPrefix = 'helpdesk_schedule';
    /**
     * @var Config
     */
    private $config;
    /**
     * @var \Mirasvit\Helpdesk\Helper\StringUtil
     */
    private $helpdeskString;
    /**
     * @var Schedule\WorkingHoursFactory
     */
    private $workingHoursFactory;

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    /**
     * @var \Magento\Framework\Model\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Model\ResourceModel\AbstractResource|null
     */
    protected $resource;

    /**
     * @var \Magento\Framework\Data\Collection\AbstractDb|null
     */
    protected $resourceCollection;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Schedule
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Mirasvit\Helpdesk\Model\Config $config
     * @param \Mirasvit\Helpdesk\Helper\Schedule $helper
     * @param \Mirasvit\Helpdesk\Helper\StringUtil $helpdeskString
     * @param \Mirasvit\Helpdesk\Model\Schedule\WorkingHoursFactory $workingHoursFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $phpSerializer;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Mirasvit\Helpdesk\Model\Config $config,
        \Mirasvit\Helpdesk\Helper\Schedule $helper,
        \Mirasvit\Helpdesk\Helper\StringUtil $helpdeskString,
        \Mirasvit\Helpdesk\Model\Schedule\WorkingHoursFactory $workingHoursFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Framework\Serialize\Serializer\Serialize $phpSerializer,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->localeDate = $localeDate;
        $this->helpdeskString = $helpdeskString;
        $this->localeResolver = $localeResolver;
        $this->config = $config;
        $this->helper = $helper;
        $this->context = $context;
        $this->registry = $registry;
        $this->workingHoursFactory = $workingHoursFactory;
        $this->serializer = $serializer;
        $this->phpSerializer = $phpSerializer;
        $this->resource = $resource;
        $this->resourceCollection = $resourceCollection;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\ResourceModel\Schedule');
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Schedule\WorkingHours[]
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getWorkingHours()
    {
        $arr = [];
        if ($this->getData('working_hours')) {
            $rawValue = (string) $this->getData('working_hours');
            try {
                $decoded = $this->serializer->unserialize($rawValue);
                if (is_array($decoded)) {
                    $arr = $decoded;
                }
            } catch (\Exception $e) {
                $recovered = false;
                if (strpos($rawValue, 'a:') === 0) {
                    try {
                        $maybe = $this->phpSerializer->unserialize($rawValue);
                        if (is_array($maybe)) {
                            $arr = $maybe;
                            $recovered = true;
                        }
                    } catch (\Exception $e2) {
                        // fall through
                    }
                }
                if (!$recovered) {
                    $this->_logger->debug(
                        'Mirasvit\\Helpdesk Schedule: legacy non-JSON working_hours cannot be decoded; '
                        . 're-save the schedule in admin to convert it. '
                        . substr($rawValue, 0, 4096),
                        ['exception' => $e]
                    );
                }
            }
        }
        $result = [];
        foreach ($this->helper->getWeekDays() as $weekdayId => $weekday) {
            $record = $this->workingHoursFactory->create();
            $record->setWeekdayId($weekdayId);
            if (isset($arr[$weekdayId])) {
                $record->setTimeFrom($arr[$weekdayId]['from']);
                $record->setTimeTo($arr[$weekdayId]['to']);
            }
            $result[$weekdayId] = $record;
        }

        return $result;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getFormattedClosedMessage()
    {
        $message = $this->getClosedMessage();

        if (!$message) {
            $message = $this->config->getScheduleDefaultClosedMessage();
        }
        if (!$message) {
            return __('');
        }

        $return = '';
        if ($this->getId()) {
            if ($dateTime = $this->getOpenTimeFrom()) {
                $return = $this->helpdeskString->nicetime($dateTime->getTimestamp());
            } elseif ($this->getActiveTo()) {
                $return = $this->helpdeskString->nicetime((int) strtotime($this->getActiveTo()));
            } else {
                //for some reason we were not able to calculate open time.
                //so return empty message
                if (strpos((string) $message, \Mirasvit\Helpdesk\Model\Config::SCHEDULE_LEFT_HOUR_TO_OPEN_PLACEHOLDER)
                        !== false) {
                    return __('');
                }
            }
        }

        $message = str_replace(
            \Mirasvit\Helpdesk\Model\Config::SCHEDULE_LEFT_HOUR_TO_OPEN_PLACEHOLDER,
            $return,
            $message
        );

        return __($message);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getOpenMessage()
    {
        $message = $this->getData('open_message');

        if (!$message) {
            $message = $this->config->getScheduleDefaultOpenMessage();
        }

        return __($message);
    }

    /**
     * Returns nearest date when this schedule will be open
     *
     * @return \DateTime|false
     */
    public function getOpenTimeFrom()
    {
        $currentDate = new \DateTime('now', new \DateTimeZone($this->getTimezone()));

        $scheduleDay = (int)$currentDate->format('w');
        $days = $this->getWorkingHours();
        //try find open time this week
        for ($i = $scheduleDay; $i < 7; $i ++) {
            if (isset($days[$i])) {
                $scheduleDateStart = new \DateTime('now', new \DateTimeZone($this->getTimezone()));

                //adds days to date. like +P0D
                $scheduleDateStart->add(new \DateInterval("P".($i - $scheduleDay)."D"));

                if ($days[$i]->getTimeFrom()) {
                    list($hour, $minute) = explode(':', $days[$i]->getTimeFrom());
                    $scheduleDateStart->setTime((int) $hour, (int) $minute);

                    if ($scheduleDateStart > $currentDate) {

                        return $scheduleDateStart; //we will start today
                    }
                }
            }
        }

        //try find open time next week
        for ($i = 0; $i <= $scheduleDay; $i ++) {
            if (isset($days[$i])) {
                $scheduleDateStart = new \DateTime('now', new \DateTimeZone($this->getTimezone()));
                //adds days to date. like +P0D
                $daysN = $i - $scheduleDay + 7;
                $scheduleDateStart->add(new \DateInterval("P".$daysN."D"));

                if ($days[$i]->getTimeFrom()) {
                    list($hour, $minute) = explode(':', $days[$i]->getTimeFrom());
                    $scheduleDateStart->setTime((int) $hour, (int) $minute);

                    if ($scheduleDateStart > $currentDate) {
                        return $scheduleDateStart; //we will start today
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        if ($this->getType() == \Mirasvit\Helpdesk\Model\Config::SCHEDULE_TYPE_ALWAYS) {
            return true;
        } elseif ($this->getType() == \Mirasvit\Helpdesk\Model\Config::SCHEDULE_TYPE_CLOSED) {
            return false;
        } elseif ($this->getType() == \Mirasvit\Helpdesk\Model\Config::SCHEDULE_TYPE_CUSTOM) {
            $currentDate = new \DateTime('now', new \DateTimeZone($this->getTimezone()));

            $scheduleDay = $currentDate->format('w');
            $days = $this->getWorkingHours();
            if (isset($days[$scheduleDay]) && !$days[$scheduleDay]->isClosed()) {
                $scheduleDateStart = new \DateTime('now', new \DateTimeZone($this->getTimezone()));
                list($hour, $minute) = explode(':', $days[$scheduleDay]->getTimeFrom());
                $scheduleDateStart->setTime((int) $hour, (int) $minute);

                $scheduleDateEnd = new \DateTime('now', new \DateTimeZone($this->getTimezone()));
                list($hour, $minute) = explode(':', $days[$scheduleDay]->getTimeTo());
                $scheduleDateEnd->setTime((int) $hour, (int) $minute);

                if ($scheduleDateStart <= $currentDate && $scheduleDateEnd >= $currentDate) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTimezoneOffset()
    {
        $date = (new \DateTime('now', new \DateTimeZone($this->getTimezone())));

        return $date->format('P') .' ' . $date->getTimezone()->getName();
    }

    /**
     * @return string|null
     */
    public function getActiveFrom()
    {
        $activeFrom = $this->getData('active_from');
        if ($activeFrom) {
            return (new \DateTime($activeFrom, new \DateTimeZone('UTC')))
                ->setTimezone(new \DateTimeZone($this->getTimezone()))
                ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getActiveTo()
    {
        $activeTo = $this->getData('active_to');
        if ($activeTo) {
            return (new \DateTime($activeTo, new \DateTimeZone('UTC')))
                ->setTimezone(new \DateTimeZone($this->getTimezone()))
                ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        }

        return null;
    }
}
