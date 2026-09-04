<?php
namespace Kdi\Consultation\Helper;

use Magento\Framework\App\Helper\AbstractHelper as MagentoAbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CustomSession extends MagentoAbstractHelper
{
    const DEFAULT_COOKIE_LIFETIME = 2592000; // 30 Days
    const CONNECTOR_COOKIE_NAME = 'hh_product_id';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @param Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Get data from cookie
     *
     * @return string
     */
    public function get()
    {
        $value = $this->cookieManager->getCookie($this->getCookieName());

        return $value;
    }

    public function getQnairValue()
    {
        $value = $this->cookieManager->getCookie('hh_cookie');
        return $value;
    }


    /**
     * Set data to cookie
     *
     * @param string|array $value
     * @param int $duration
     *
     * @return void
     */
    public function set($value, $duration = null)
    {
        $metadata = $this->cookieMetadataFactory
        ->createPublicCookieMetadata()
        ->setDuration($duration ?: static::DEFAULT_COOKIE_LIFETIME)
        ->setPath($this->sessionManager->getCookiePath())
        ->setDomain($this->sessionManager->getCookieDomain())
        ->setSecure(true)
        ->setHttpOnly(true);
            
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->cookieManager->setPublicCookie(
            $this->getCookieName(),
            $value,
            $metadata
        );
    }

    /**
     * delete cookie remote address
     *
     * @return void
     */
    public function delete()
    {
        $this->cookieManager->deleteCookie(
            $this->getCookieName(),
            $this->cookieMetadataFactory
                ->createCookieMetadata()
                ->setPath($this->sessionManager->getCookiePath())
                ->setDomain($this->sessionManager->getCookieDomain())
        );
    }

    /**
     * Used to get cookies name (key) by which data can be set or get
     *
     * @return string
     */
    public function getCookieName()
    {
        return static::CONNECTOR_COOKIE_NAME;
    }

    /**
     * Check string is valid JSON
     *
     * @param $string
     * @return bool
     */
    public function isJson($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}
?>