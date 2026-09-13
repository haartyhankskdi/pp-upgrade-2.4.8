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
use Mirasvit\Helpdesk\Api\Data\TemplateInterface;
use Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory as FieldCollectionFactory;

/**
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Template\Collection|\Mirasvit\Helpdesk\Model\Template[] getCollection()
 * @method \Mirasvit\Helpdesk\Model\Template load(int $id)
 * @method bool getIsMassDelete()
 * @method \Mirasvit\Helpdesk\Model\Template setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method \Mirasvit\Helpdesk\Model\Template setIsMassStatus(bool $flag)
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Template getResource()
 */
class Template extends \Magento\Framework\Model\AbstractModel implements IdentityInterface, TemplateInterface
{
    const CACHE_TAG = 'helpdesk_template';

    /**
     * @var string
     */
    protected $_cacheTag = 'helpdesk_template';

    /**
     * @var string
     */
    protected $_eventPrefix = 'helpdesk_template';

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
     * @var \Magento\Store\Model\StoreFactory
     */
    protected $storeFactory;

    /**
     * @var \Mirasvit\Core\Helper\ParseVariables
     */
    protected $mstcoreParseVariables;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    protected $auth;

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

    protected $fieldCollectionFactory;

    /**
     * @param \Magento\Store\Model\StoreFactory                       $storeFactory
     * @param \Mirasvit\Core\Helper\ParseVariables                 $mstcoreParseVariables
     * @param \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig
     * @param \Magento\Backend\Model\Auth                             $auth
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Mirasvit\Core\Helper\ParseVariables $mstcoreParseVariables,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        FieldCollectionFactory $fieldCollectionFactory,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeFactory = $storeFactory;
        $this->mstcoreParseVariables = $mstcoreParseVariables;
        $this->scopeConfig = $scopeConfig;
        $this->auth = $auth;
        $this->context = $context;
        $this->registry = $registry;
        $this->fieldCollectionFactory = $fieldCollectionFactory;
        $this->resource = $resource;
        $this->resourceCollection = $resourceCollection;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\ResourceModel\Template');
    }

    /**
     * @param bool $emptyOption
     * @return array
     */
    public function toOptionArray($emptyOption = false)
    {
        return $this->getCollection()->toOptionArray($emptyOption);
    }

    /************************/

    /**
     * @return int
     */
    public function getTemplateId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @param int $templateId
     * @return $this
     */
    public function setTemplateId($templateId)
    {
        return $this->setData(self::ID, $templateId);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::KEY_NAME);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::KEY_NAME, $name);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->getData(self::KEY_TEMPLATE);
    }

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        return $this->setData(self::KEY_TEMPLATE, $template);
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
     * @return int[]
     */
    public function getStoreIds()
    {
        return $this->getData(self::KEY_STORE_IDS) ?: [];
    }

    /**
     * @param int[] $storeIds
     * @return $this
     */
    public function setStoreIds(array $storeIds)
    {
        return $this->setData(self::KEY_STORE_IDS, $storeIds);
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::KEY_CREATED_AT);
    }

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::KEY_UPDATED_AT);
    }

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::KEY_UPDATED_AT, $updatedAt);
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     *
     * @return string
     */
    public function getParsedTemplate($ticket)
    {
        $storeId = $ticket->getStoreId();
        $storeOb = $this->storeFactory->create()->load($storeId);
        if (!$name = $this->scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $storeId
        )) {
            $name = $storeOb->getName();
        }
        $store = new \Magento\Framework\DataObject([
            'name' => $name,
            'phone' => $this->scopeConfig->getValue(
                'general/store_information/phone',
                \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $storeId
            ),
            'address' => $this->scopeConfig->getValue(
                'general/store_information/address',
                \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $storeId
            ),
        ]);
        /** @var \Magento\User\Model\User $user */
        $user = $this->auth->getUser();

        $fieldCollection = $this->fieldCollectionFactory->create()
            ->addStoreFilter($storeId)
            ->addFieldToFilter('is_active', 1);

        $fieldsByCode = [];
        foreach ($fieldCollection as $field) {
            $fieldsByCode[$field->getCode()] = $field;
        }

        foreach ($ticket->getData() as $code => $value) {
            if (strpos($code, 'f_') !== 0 || !$value) {
                continue;
            }

            if (isset($fieldsByCode[$code]) && $fieldsByCode[$code]->getType() === \Mirasvit\Helpdesk\Model\Field::TYPE_SELECT) {
                $options = $fieldsByCode[$code]->getValues();
                if (isset($options[$value])) {
                    $ticket->setData($code, $options[$value]);
                }
            }
        }

        $result = $this->mstcoreParseVariables->parse(
            (string) $this->getTemplate(),
            [
            'ticket' => $ticket,
            'store' => $store,
            'user' => $user,
            ],
            [],
            $store->getId()
        );

        return $result;
    }
}
