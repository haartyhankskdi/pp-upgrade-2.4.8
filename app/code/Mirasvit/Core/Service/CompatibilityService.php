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
 * @package   mirasvit/module-core
 * @version   1.7.20
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Core\Service;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;

class CompatibilityService
{
    /**
     * Stands in for a version string that carries no usable `major.minor`, and reads as "newer
     * than anything the isNN() checks below ask about" — so none of them match, which is the
     * right answer for a source install or a beta.
     */
    const UNKNOWN_VERSION = '10.0.0';

    private static $version;

    /**
     * @return bool
     */
    public static function isMarketplace()
    {
        $flag = true;

        /** mp comment start */

        $flag = false;

        /** mp comment end */

        return $flag;
    }

    /**
     * @return bool
     */
    public static function is20()
    {
        list($a, $b) = explode('.', self::getVersion());

        return $a == 2 && $b == 0;
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        if (empty(self::$version)) {
            /** @var CacheInterface $cache */
            $cache   = self::getObjectManager()->get(CacheInterface::class);
            $version = $cache->load(__CLASS__);

            if (!$version) {
                /** @var \Magento\Framework\App\ProductMetadata $metadata */
                $metadata = self::getObjectManager()->get('Magento\Framework\App\ProductMetadata');

                $version = $metadata->getVersion();

                $cache->save($version, __CLASS__);
            }

            // Normalised on the way OUT, not before the cache write, so a store that already
            // cached an unusable value is fixed by this upgrade rather than staying broken until
            // someone flushes the cache.
            self::$version = self::normalizeVersion((string)$version);
        }

        return self::$version;
    }

    /**
     * A version string the `isNN()` checks below can safely `explode('.')`.
     *
     * Anything without a numeric `major.minor` is reported as 10.0.0 — "newer than every version
     * this class knows about" — which is the same answer the `no-version` special case has always
     * given, generalised to the inputs that actually occur.
     *
     * This is load-bearing on every Magento SOURCE install, which is what CI runs and what a
     * developer tarball is. There, `magento/product-community-edition` is not installed at all and
     * ProductMetadata falls back to the root composer package — `magento/magento2ce`, pretty
     * version `dev-develop`. That has no dot, so `list($a, $b) = explode('.', ...)` left $b
     * undefined; in developer mode Magento escalates the warning to an exception, and because the
     * callers are the admin MENU plugins, every Mirasvit admin page in every module returned a 500.
     *
     * @param string $version
     *
     * @return string
     */
    public static function normalizeVersion($version)
    {
        $version = (string)$version;

        if (strpos($version, 'no-version') !== false) {
            return self::UNKNOWN_VERSION; //only for beta versions of magento
        }

        return preg_match('/^\d+\.\d+/', $version) ? $version : self::UNKNOWN_VERSION;
    }

    /**
     * @return ObjectManager
     */
    public static function getObjectManager()
    {
        return ObjectManager::getInstance();
    }

    /**
     * @return bool
     */
    public static function is21()
    {
        list($a, $b) = explode('.', self::getVersion());

        return $a == 2 && $b == 1;
    }

    /**
     * @return bool
     */
    public static function is22()
    {
        list($a, $b) = explode('.', self::getVersion());

        return $a == 2 && $b == 2;
    }

    /**
     * @return bool
     */
    public static function is23()
    {
        list($a, $b) = explode('.', self::getVersion());

        return $a == 2 && $b == 3;
    }

    /**
     * @return bool
     */
    public static function is24()
    {
        list($a, $b) = explode('.', self::getVersion());

        return $a == 2 && $b == 4;
    }

    /**
     * @return bool
     */
    public static function isEnterprise()
    {
        return self::getEdition() === 'Enterprise';
    }

    /**
     * @return string
     */
    public static function getEdition()
    {
        /** @var \Magento\Framework\App\ProductMetadata $metadata */
        $metadata = self::getObjectManager()->get('Magento\Framework\App\ProductMetadata');

        if (self::hasModule('Magento_Enterprise')) {
            return 'Enterprise';
        }

        return $metadata->getEdition();
    }

    /**
     * @param string $moduleName
     *
     * @return bool
     */
    public static function hasModule($moduleName)
    {
        /** @var \Magento\Framework\Module\FullModuleList $list */
        $list = self::getObjectManager()->get('Magento\Framework\Module\FullModuleList');

        return $list->has($moduleName);
    }
}
