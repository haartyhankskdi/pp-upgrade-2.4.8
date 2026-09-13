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
use Mirasvit\Helpdesk\Api\Data\FieldInterface;

/**
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection|\Mirasvit\Helpdesk\Model\Field[] getCollection()
 * @method \Mirasvit\Helpdesk\Model\Field load(int $id, string $field = null)
 * @method bool getIsMassDelete()
 * @method \Mirasvit\Helpdesk\Model\Field setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method \Mirasvit\Helpdesk\Model\Field setIsMassStatus(bool $flag)
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Field getResource()
 * @method bool getIsNew()
 * @method \Mirasvit\Helpdesk\Model\Field setIsNew(bool $flag)
 * @method bool getIsRequiredStaff()
 * @method \Mirasvit\Helpdesk\Model\Field setIsRequiredStaff(bool $flag)
 * @method bool getIsRequiredCustomer()
 * @method \Mirasvit\Helpdesk\Model\Field setIsRequiredCustomer(bool $flag)
 */
class Field extends \Magento\Framework\Model\AbstractModel implements IdentityInterface, FieldInterface
{
    const CACHE_TAG = 'helpdesk_field';

    const TYPE_SELECT   = 'select';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_TEXT     = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_DATE     = 'date';

    /**
     * @var string
     */
    protected $_cacheTag = 'helpdesk_field';

    /**
     * @var string
     */
    protected $_eventPrefix = 'helpdesk_field';
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

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
     * @var \Mirasvit\Helpdesk\Model\FieldFactory
     */
    protected $fieldFactory;

    /**
     * @var \Magento\Framework\Model\ResourceModel\AbstractResource|null
     */
    protected $resource;

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
     * @var \Magento\Framework\Data\Collection\AbstractDb|null
     */
    protected $resourceCollection;


    /**
     * @param \Mirasvit\Helpdesk\Model\FieldFactory                   $fieldFactory
     * @param \Mirasvit\Helpdesk\Helper\Storeview                     $helpdeskStoreview
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\App\ResourceConnection               $resourceConnection
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     * @param array                                                   $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\FieldFactory $fieldFactory,
        \Mirasvit\Helpdesk\Helper\Storeview $helpdeskStoreview,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->fieldFactory = $fieldFactory;
        $this->resource = $resource;
        $this->helpdeskStoreview = $helpdeskStoreview;
        $this->context = $context;
        $this->registry = $registry;
        $this->resourceConnection = $resourceConnection;
        $this->resourceCollection = $resourceCollection;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\ResourceModel\Field');
    }

    /**
     * @param bool|false $emptyOption
     * @return array
     */
    public function toOptionArray($emptyOption = false)
    {
        return $this->getCollection()->toOptionArray($emptyOption);
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
     * @return string
     */
    public function getDescription()
    {
        return $this->helpdeskStoreview->getStoreViewValue($this, 'description');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setDescription($value)
    {
        $this->helpdeskStoreview->setStoreViewValue($this, 'description', $value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValues($value)
    {
        $this->helpdeskStoreview->setStoreViewValue($this, 'values', $value);

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

        if (isset($data['description']) && strpos((string) $data['description'], 'a:') !== 0) {
            $this->setDescription($data['description']);
            unset($data['description']);
        }

        if (isset($data['values']) && strpos((string) $data['values'], 'a:') !== 0) {
            $this->setValues($data['values']);
            unset($data['values']);
        }

        return parent::addData($data);
    }
    /************************/

    /**
     * @param string|bool $emptyOption
     * @return array
     */
    public function getValues($emptyOption = false)
    {
        $values = $this->helpdeskStoreview->getStoreViewValue($this, 'values');
        $arr = explode("\n", (string)$values);
        $values = [];
        foreach ($arr as $value) {
            $value = explode('|', $value);
            if (count($value) >= 2) {
                $values[trim($value[0])] = trim($value[1]);
            }
        }
        if ($emptyOption) {
            $res = [];
            $res[] = ['value' => '', 'label' => __('-- Please Select --')];
            foreach ($values as $index => $value) {
                $res[] = [
                   'value' => $index,
                   'label' => $value,
                ];
            }
            $values = $res;
        }

        return $values;
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        if (!$this->getId()) {
            $this->setIsNew(true);
        }

        return parent::beforeSave();
    }

    /**
     * Create a new field in the tickets table
     *
     * @return void
     */
    public function afterCommitCallback()
    {
        parent::afterCommitCallback();

        if ($this->getIsNew()) {
            $tableName = $this->resourceConnection->getTableName('mst_helpdesk_ticket');
            $query = "ALTER TABLE `{$tableName}` ADD `{$this->getCode()}` TEXT";
            $connection = $this->resourceConnection->getConnection();
            $connection->query($query);
            $connection->resetDdlCache();
        }
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeDelete()
    {
        $field = $this->fieldFactory->create()->load($this->getId());
        $this->setDbCode($field->getCode()); //used in afterDeleteCommit

        return parent::beforeDelete();
    }

    /**
     * Remove field from the tickets table
     *
     * @return void
     */
    public function afterDeleteCommit()
    {
        parent::afterDeleteCommit();
        $tableName = $this->resourceConnection->getTableName('mst_helpdesk_ticket');
        $query = "ALTER TABLE `{$tableName}` DROP `{$this->getDbCode()}`";
        $connection = $this->resourceConnection->getConnection();
        $connection->query($query);
        $connection->resetDdlCache();
    }

    /**
     * @return string
     */
    public function getGridType()
    {
        switch ($this->getType()) {
            case 'date':
                $type = 'date';
                break;
            case 'select':
            case 'checkbox':
                $type = 'options';
                break;
            default:
                $type = 'text';
                break;
        }

        return $type;
    }

    /**
     * @return array|string
     */
    public function getGridOptions()
    {
        if ($this->getType() == 'checkbox') {
            return [
                0 => __('No'),
                1 => __('Yes'),
                ];
        }

        return $this->getValues();
    }

    /**
     * @return int
     */
    public function getFieldId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @param int $fieldId
     * @return $this
     */
    public function setFieldId($fieldId)
    {
        return $this->setData(self::ID, $fieldId);
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
    public function getType()
    {
        return $this->getData(self::KEY_TYPE);
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        return $this->setData(self::KEY_TYPE, $type);
    }

    /**
     * @return int
     */
    public function getIsActive()
    {
        return $this->getData(self::KEY_IS_ACTIVE);
    }

    /**
     * @param int $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::KEY_IS_ACTIVE, $isActive);
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
