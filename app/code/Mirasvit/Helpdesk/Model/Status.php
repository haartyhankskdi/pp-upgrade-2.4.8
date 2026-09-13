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

use Magento\Framework\DataObject\IdentityInterface;
use Mirasvit\Helpdesk\Api\Data\StatusInterface;

/**
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Status\Collection|\Mirasvit\Helpdesk\Model\Status[] getCollection()
 * @method \Mirasvit\Helpdesk\Model\Status load(int $id)
 * @method bool getIsMassDelete()
 * @method \Mirasvit\Helpdesk\Model\Status setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method \Mirasvit\Helpdesk\Model\Status setIsMassStatus(bool $flag)
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Status getResource()
 * @method string getCreatedAt()
 * @method $this setCreatedAt(string $param)
 * @method string getUpdatedAt()
 * @method $this setUpdatedAt(string $param)
 * @method array getStoreIds()
 * @method $this setStoreIds(array $param)
 */
class Status extends \Magento\Framework\Model\AbstractModel implements
    IdentityInterface,
    \Magento\Framework\Data\OptionSourceInterface,
    StatusInterface
{
    const CACHE_TAG = 'helpdesk_status';

    /**
     * @var string
     */
    protected $_cacheTag = 'helpdesk_status';

    /**
     * @var string
     */
    protected $_eventPrefix = 'helpdesk_status';

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
     * @var \Mirasvit\Helpdesk\Helper\Storeview
     */
    protected $helpdeskStoreview;

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
     * @param \Mirasvit\Helpdesk\Helper\Storeview                     $helpdeskStoreview
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     * @param array                                                   $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Helper\Storeview $helpdeskStoreview,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helpdeskStoreview = $helpdeskStoreview;
        $this->context = $context;
        $this->registry = $registry;
        $this->resource = $resource;
        $this->resourceCollection = $resourceCollection;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\ResourceModel\Status');
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        //empty option is false, because we don't want to show 'Please, select' in General Settings.
        return $this->getCollection()->toOptionArray(false);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->helpdeskStoreview->getStoreViewValue($this, 'name');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setName($value)
    {
        $this->helpdeskStoreview->setStoreViewValue($this, 'name', $value);

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function addData(array $data)
    {
        if (isset($data['name']) && strpos((string) $data['name'], 'a:') !== 0) {
            $this->setName($data['name']);
            unset($data['name']);
        }

        return parent::addData($data);
    }

    /**
     * @param string $code
     * @return bool|Status
     */
    public function loadByCode($code)
    {
        $collection = $this->getCollection()
        ->addFieldToFilter('code', $code);
        if ($collection->count() > 0) {
            return $collection->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Prepare collection for dropdowns.
     *
     * @param int|\Magento\Store\Model\Store $store
     *
     * @return \Mirasvit\Helpdesk\Model\ResourceModel\Status\Collection|\Mirasvit\Helpdesk\Model\Status[]
     */
    public function getPreparedCollection($store)
    {
        if (is_object($store)) {
            $store = $store->getStoreId();
        }

        return $this->getCollection()
            ->addStoreFilter($store)
            ->setOrder('sort_order', \Mirasvit\Helpdesk\Model\Config::DEFAULT_SORT_ORDER);
    }

    /**
     * @return int
     */
    public function getStatusId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @param int $statusId
     * @return $this
     */
    public function setStatusId($statusId)
    {
        return $this->setData(self::ID, $statusId);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->getData(self::KEY_CODE);
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        return $this->setData(self::KEY_CODE, $code);
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->getData(self::KEY_COLOR);
    }

    /**
     * @param string $color
     * @return $this
     */
    public function setColor($color)
    {
        return $this->setData(self::KEY_COLOR, $color);
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->getData(self::KEY_SORT_ORDER);
    }

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::KEY_SORT_ORDER, $sortOrder);
    }
}
