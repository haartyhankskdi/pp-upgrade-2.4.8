<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\LNAPI\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use \Magento\Framework\Encryption\EncryptorInterface;

class System extends AbstractHelper
{

    const STATUS = 'lexis_nexis_configuration/general/enable';
    const ID = 'lexis_nexis_configuration/general/id';
    const IKEY = 'lexis_nexis_configuration/general/ikey';
    const JOURNEY_ID = 'lexis_nexis_configuration/general/journey_id';

    protected $scopeConfig;
    protected $encryptorInterface;

    /**
     * @param Context $context
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfigInterface, EncryptorInterface $encryptorInterface)
    {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfigInterface;
        $this->encryptorInterface = $encryptorInterface;
    }

    /**
     * Retrieve the store config value by path
     *
     * @param string $path
     * @param int|null $storeId
     * @return mixed
     */
    public function getConfigValue($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }


    /**
     * Decrypts the provided encrypted value.
     *
     * @param string $value
     * @return string|null
     */
    public function decryptValue($value)
    {
        if (empty($value)) {
            return null;
        }
        try {
            return $this->encryptorInterface->decrypt($value);
        } catch (\Exception $e) {
            $this->_logger->error('Decryption failed: ' . $e->getMessage());
            return null;
        }
    }
}
