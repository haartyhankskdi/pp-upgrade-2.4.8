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

/**
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Gateway\Collection|\Mirasvit\Helpdesk\Model\Gateway[] getCollection()
 * @method \Mirasvit\Helpdesk\Model\Gateway load(int $id)
 * @method bool getIsMassDelete()
 * @method \Mirasvit\Helpdesk\Model\Gateway setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method \Mirasvit\Helpdesk\Model\Gateway setIsMassStatus(bool $flag)
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Gateway getResource()
 * @method string getName()
 * @method \Mirasvit\Helpdesk\Model\Gateway setName(string $param)
 * @method string getFetchedAt()
 * @method \Mirasvit\Helpdesk\Model\Gateway setFetchedAt(string $param)
 * @method string getLastFetchResult()
 * @method \Mirasvit\Helpdesk\Model\Gateway setLastFetchResult(string $param)
 * @method int getFetchFrequency()
 * @method \Mirasvit\Helpdesk\Model\Gateway setFetchFrequency(int $param)
 * @method string getHost()
 * @method \Mirasvit\Helpdesk\Model\Gateway setHost(string $param)
 * @method string getFolder()
 * @method \Mirasvit\Helpdesk\Model\Gateway setFolder(string $param)
 * @method string getPort()
 * @method \Mirasvit\Helpdesk\Model\Gateway setPort(string $param)
 * @method int getStoreId()
 * @method \Mirasvit\Helpdesk\Model\Gateway setStoreId(int $param)
 * @method int getFetchMax()
 * @method \Mirasvit\Helpdesk\Model\Gateway setFetchMax(int $param)
 * @method int getFetchLimit()
 * @method \Mirasvit\Helpdesk\Model\Gateway setFetchLimit(int $param)
 * @method bool getIsDeleteEmails()
 * @method \Mirasvit\Helpdesk\Model\Gateway setIsDeleteEmails(bool $param)
 * @method string getProtocol()
 * @method \Mirasvit\Helpdesk\Model\Gateway setProtocol(string $param)
 * @method string getEncryption()
 * @method \Mirasvit\Helpdesk\Model\Gateway setEncryption(string $param)
 * @method string getLogin()
 * @method \Mirasvit\Helpdesk\Model\Gateway setLogin(string $param)
 * @method string getAuthorizationType()
 * @method \Mirasvit\Helpdesk\Model\Gateway setAuthorizationType(string $param)
 * @method string getTenant()
 * @method \Mirasvit\Helpdesk\Model\Gateway setTenant(string $param)
 * @method string getClientId()
 * @method \Mirasvit\Helpdesk\Model\Gateway setClientId(string $param)
 * @method int getTokenExpires()
 * @method \Mirasvit\Helpdesk\Model\Gateway setTokenExpires(int $param)
 * @method string getConnectionStatus()
 * @method \Mirasvit\Helpdesk\Model\Gateway setConnectionStatus(string $param)
 */
class Gateway extends \Magento\Framework\Model\AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'helpdesk_gateway';

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var string
     */
    protected $_eventPrefix = 'helpdesk_gateway';

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        $tags = [];
        if ($this->_cacheTag) {
            /** @var string $cacheTag */
            $cacheTag = $this->_cacheTag;
            $tags[]   = $cacheTag . '_' . $this->getId();
        }

        return $tags;
    }

    /**
     * @var \Mirasvit\Helpdesk\Model\DepartmentFactory
     */
    protected $departmentFactory;

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
     * Gateway constructor.
     * @param DepartmentFactory $departmentFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\DepartmentFactory $departmentFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->encryptor = $encryptor;
        $this->departmentFactory = $departmentFactory;
        $this->context = $context;
        $this->registry = $registry;
        $this->resource = $resource;
        $this->resourceCollection = $resourceCollection;

        if ($this->context->getAppState()->getAreaCode() != \Magento\Framework\App\Area::AREA_CRONTAB) {
            $this->_cacheTag = 'helpdesk_gateway';
        }

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\ResourceModel\Gateway');
    }

    /**
     * Override of standard getter function.
     * Contains errcheck for legacy users, who can have assigned deleted departments to a gateway
     *
     * @return int|false
     */
    public function getDepartmentId()
    {
        $id = $this->getData('department_id');
        $department = $this->departmentFactory->create()->load($id);
        if (!$department) {
            return false;
        }

        return $id;
    }

    /**
     * @param bool $emptyOption
     * @return array
     */
    public function toOptionArray($emptyOption = false)
    {
        return $this->getCollection()->toOptionArray($emptyOption);
    }


    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $value =  $this->encryptor->encrypt($password);
        $this->setData("password", $value);
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        $value = (string)$this->getData("password");
        if (strlen($value) > 40) {
            return $this->encryptor->decrypt($value);
        }
        return $value;
    }

    /**
     * @param string $clientSecret
     * @return $this
     */
    public function setClientSecret($clientSecret)
    {
        if (!empty($clientSecret)) {
            $value = $this->encryptor->encrypt($clientSecret);
            $this->setData("client_secret", $value);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        $value = (string)$this->getData("client_secret");
        if (strlen($value) > 40) {
            return $this->encryptor->decrypt($value);
        }
        return $value;
    }

    /**
     * @param string $accessToken
     * @return $this
     */
    public function setAccessToken($accessToken)
    {
        if (!empty($accessToken)) {
            $value = $this->encryptor->encrypt($accessToken);
            $this->setData("access_token", $value);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        $value = (string)$this->getData("access_token");
        if (strlen($value) > 40) {
            return $this->encryptor->decrypt($value);
        }
        return $value;
    }

    /**
     * @param string $refreshToken
     * @return $this
     */
    public function setRefreshToken($refreshToken)
    {
        if (!empty($refreshToken)) {
            $value = $this->encryptor->encrypt($refreshToken);
            $this->setData("refresh_token", $value);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        $value = (string)$this->getData("refresh_token");
        if (strlen($value) > 40) {
            return $this->encryptor->decrypt($value);
        }
        return $value;
    }
}
