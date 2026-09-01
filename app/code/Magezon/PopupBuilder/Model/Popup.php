<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Model;

use Magezon\PopupBuilder\Api\Data\PopupInterface;

class Popup extends \Magento\Framework\Model\AbstractModel implements \Magezon\PopupBuilder\Api\Data\PopupInterface
{
    /**
     */
    const CACHE_TAG = 'popupbuilder_popup';

    /**#@-*/
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'popupbuilder_popup';

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magezon\Core\Helper\Data
     */
    protected $coreHelper;

    /**
     * @var \Magezon\PopupBuilder\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magezon\UiChooserLayout\Model\Validato
     */
    protected $validator;

    /**
     * @var \Magento\Newsletter\Model\Subscriber
     */
    protected $subscription;

    /**
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Checkout\Model\Session                              $checkoutSession
     * @param \Magento\Customer\Model\Session                              $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface         $timezone
     * @param \Magento\Newsletter\Model\SubscriberFactory                  $subscriberFactory
     * @param \Magezon\Core\Helper\Data                                    $coreHelper
     * @param \Magezon\PopupBuilder\Helper\Data                            $dataHelper
     * @param \Magezon\UiChooserLayout\Model\Validator                     $validator
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magezon\Core\Helper\Data $coreHelper,
        \Magezon\PopupBuilder\Helper\Data $dataHelper,
        \Magezon\UiChooserLayout\Model\Validator $validator,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection);
        $this->checkoutSession   = $checkoutSession;
        $this->subscriberFactory = $subscriberFactory;
        $this->customerSession   = $customerSession;
        $this->timezone          = $timezone;
        $this->coreHelper        = $coreHelper;
        $this->dataHelper        = $dataHelper;
        $this->validator         = $validator;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magezon\PopupBuilder\Model\ResourceModel\Popup::class);
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return parent::getData(self::POPUP_ID);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return PopupInterface
     */
    public function setId($id)
    {
        return $this->setData(self::POPUP_ID, $id);
    }

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName()
    {
        return parent::getData(self::NAME);
    }

    /**
     * Set name
     *
     * @param string $name
     * @return PopupInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get content
     *
     * @return string|null
     */
    public function getContent()
    {
        return parent::getData(self::CONTENT);
    }

    /**
     * Set content
     *
     * @param string $content
     * @return PopupInterface
     */
    public function setContent($content)
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * Get conditions
     *
     * @param string|null $key
     * @return string|null
     */
    public function getConditions($key = '')
    {
        $settings = $this->dataHelper->dataPreprocessing($this->coreHelper->unserialize($this->getData('conditions')));
        if ($key) {
            return isset($settings[$key]) ? $settings[$key] : '';
        }
        return $settings;
    }

    /**
     * Set conditions
     *
     * @param string $conditions
     * @return PopupInterface
     */
    public function setConditions($conditions)
    {
        return $this->setData(self::CONDITIONS, $conditions);
    }

    /**
     * Get display settings
     *
     * @param string|null $key
     * @return string|null
     */
    public function getDisplaySettings($key = '')
    {
        $settings = $this->dataHelper->dataPreprocessing(
            $this->coreHelper->unserialize($this->getData('display_settings'))
        );
        if ($key) {
            return isset($settings[$key]) ? $settings[$key] : '';
        }
        return $settings;
    }

    /**
     * Set display settings
     *
     * @param string $displaySettings
     * @return PopupInterface
     */
    public function setDisplaySettings($displaySettings)
    {
        return $this->setData(self::DISPLAY_SETTINGS, $displaySettings);
    }

    /**
     * Get style settings
     *
     * @param string|null $key
     * @return string|null
     */
    public function getStyleSettings($key = '')
    {
        $settings = $this->dataHelper->dataPreprocessing(
            $this->coreHelper->unserialize($this->getData('style_settings'))
        );
        if ($key) {
            $keys = explode('/', $key);
            if (count($keys) == 2) {
                $key1 = $settings[$keys[0]];
                return isset($key1[$keys[1]]) ? $key1[$keys[1]] : '';
            }
            return isset($settings[$key]) ? $settings[$key] : '';
        }
        return $settings;
    }

    /**
     * Set style settings
     *
     * @param string $styleSettings
     * @return PopupInterface
     */
    public function setStyleSettings($styleSettings)
    {
        return $this->setData(self::STYLE_SETTINGS, $styleSettings);
    }

    /**
     * Get from date
     *
     * @return string|null
     */
    public function getFromDate()
    {
        return parent::getData(self::FROM_DATE);
    }

    /**
     * Set from date
     *
     * @param string $fromDate
     * @return PopupInterface
     */
    public function setFromDate($fromDate)
    {
        return $this->setData(self::FROM_DATE, $fromDate);
    }

    /**
     * Get to date
     *
     * @return string|null
     */
    public function getToDate()
    {
        return parent::getData(self::TO_DATE);
    }

    /**
     * Set to date
     *
     * @param string $toDate
     * @return PopupInterface
     */
    public function setToDate($toDate)
    {
        return $this->setData(self::TO_DATE, $toDate);
    }

    /**
     * Is active
     *
     * @return bool|null
     */
    public function isActive()
    {
        return parent::getData(self::IS_ACTIVE);
    }

    /**
     * Set is active
     *
     * @param int|bool $isActive
     * @return PopupInterface
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreationTime()
    {
        return parent::getData(self::CREATION_TIME);
    }

    /**
     * Set creation time
     *
     * @param string $creationTime
     * @return PopupInterface
     */
    public function setCreationTime($creationTime)
    {
        return $this->setData(self::CREATION_TIME, $creationTime);
    }

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdateTime()
    {
        return parent::getData(self::UPDATE_TIME);
    }

    /**
     * Set update time
     *
     * @param string $updateTime
     * @return PopupInterface
     */
    public function setUpdateTime($updateTime)
    {
        return $this->setData(self::UPDATE_TIME, $updateTime);
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        if (!$this->hasData('is_valid')) {
            $valid = false;
            if ($this->getConditions('hide_subscribers') && $this->getIsSubscribed()) {
                return false;
            }
            $currentDayofweek = (int)$this->timezone->date()->format('w');
            if (($daysOfWeek = $this->getConditions('days_of_week')) && !in_array($currentDayofweek, $daysOfWeek)) {
                return false;
            }
            $currentHour = (int)$this->timezone->date()->format('H');
            $fromHour    = $this->getConditions('from_hour');
            $toHour      = $this->getConditions('to_hour');
            if ($fromHour != '' || $toHour != '') {
                if ($fromHour != '' && $currentHour < $fromHour) {
                    return false;
                }
                if ($toHour != '' && $currentHour > $toHour) {
                    return false;
                }
            }
            if ($groups = $this->getData('customer_group_id')) {
                $groupId = $this->customerSession->getCustomerGroupId();
                if (!in_array($groupId, $groups)) {
                    return false;
                }
            }
            if ($rules = $this->getData('rule_id')) {
                $quote   = $this->checkoutSession->getQuote();
                $ruleIds = explode(',', $quote->getAppliedRuleIds());
                $matches = array_intersect($rules, $ruleIds);
                if (empty($matches)) {
                    return false;
                }
            }
            $conditions = $this->getConditions();
            if (isset($conditions['conditions']) && is_array($conditions['conditions'])) {
                if (!isset($conditions['page_load'])) {
                    $conditions['page_load'] = false;
                }
                if (!isset($conditions['scrolling'])) {
                    $conditions['scrolling'] = false;
                }
                if (!isset($conditions['scrolling_to'])) {
                    $conditions['scrolling_to'] = false;
                }
                if (!isset($conditions['hover_on'])) {
                    $conditions['hover_on'] = false;
                }
                if (!isset($conditions['click'])) {
                    $conditions['click'] = false;
                }
                if (!isset($conditions['inactivity'])) {
                    $conditions['inactivity'] = false;
                }
                if (!isset($conditions['exit_intent'])) {
                    $conditions['exit_intent'] = false;
                }
                if ($conditions['page_load'] || $conditions['scrolling'] || $conditions['scrolling_to'] || $conditions['hover_on'] || $conditions['click'] || $conditions['inactivity'] || $conditions['exit_intent']) {
                    $valid = $this->validator->isValid($conditions['conditions']);
                }
            }
            $this->setData('is_valid', $valid);
        }
        return $this->getData('is_valid');
    }

    /**
     * @return string
     */
    public function getHtmlId()
    {
        return 'popupbuilder-popup' . $this->getId();
    }

    /**
     * Gets Customer subscription status
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsSubscribed()
    {
        return $this->getSubscriptionObject()->isSubscribed();
    }

    /**
     * Retrieve the subscription object (i.e. the subscriber).
     *
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function getSubscriptionObject()
    {
        if ($this->subscription === null) {
            $this->subscription = $this->subscriberFactory->create()
                ->loadByCustomerId($this->customerSession->getCustomerId());
        }

        return $this->subscription;
    }
}
