<?php

declare(strict_types=1);

namespace Kdi\ImageUpload\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * Module config path prefix
     */
    const XML_PATH_PREFIX = 'kdi_imageupload/';
    

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * Get an arbitrary store config value by full XML path.
     *
     * @param  string          $path    Full config path e.g. 'section/group/field'
     * @param  int|null        $storeId Store ID (null = current store)
     * @param  string          $scope
     * @return mixed
     */
    public function getConfig(
        string $path,
        ?int $storeId = null,
        string $scope = ScopeInterface::SCOPE_STORE
    ) {
        return $this->scopeConfig->getValue(
            $path,
            $scope,
            $storeId ?? $this->getCurrentStoreId()
        );
    }

    /**
     * Shorthand: get a value under the module's own config prefix.
     *
     * Example: getModuleConfig('general/enabled')
     *   resolves to 'kdi_imageupload/general/enabled'
     *
     * @param  string   $path
     * @param  int|null $storeId
     * @param  string   $scope
     * @return mixed
     */
    public function getModuleConfig(
        string $path,
        ?int $storeId = null,
        string $scope = ScopeInterface::SCOPE_STORE
    ) {
        return $this->getConfig(
            self::XML_PATH_PREFIX . ltrim($path, '/'),
            $storeId,
            $scope
        );
    }

    /**
     * Get module config value as boolean.
     * Uses Magento's isSetFlag — works correctly with 0/1 string values.
     *
     * @param  string   $path
     * @param  int|null $storeId
     * @param  string   $scope
     * @return bool
     */
    public function isEnabled(
        string $path,
        ?int $storeId = null,
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PREFIX . ltrim($path, '/'),
            $scope,
            $storeId ?? $this->getCurrentStoreId()
        );
    }

    /**
     * Get config value at website scope.
     *
     * @param  string   $path
     * @param  int|null $websiteId
     * @return mixed
     */
    public function getWebsiteConfig(string $path, ?int $websiteId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get the current store ID.
     *
     * @return int
     */
    public function getCurrentStoreId(): int
    {
        try {
            return (int) $this->storeManager->getStore()->getId();
        } catch (\Exception $e) {
            return 0;
        }
    }
}