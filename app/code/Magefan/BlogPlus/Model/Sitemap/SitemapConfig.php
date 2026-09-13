<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogPlus\Model\Sitemap;

use Magefan\Blog\Api\SitemapConfigInterface;

class SitemapConfig extends \Magefan\Blog\Model\Config implements SitemapConfigInterface
{
    /**
     * Check if sitemap is enabled
     *
     * @param string $page
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabledSitemap($page, $storeId = null):bool
    {
        if ($page == 'author') {
            return $this->isEnabled($storeId)
                && $this->getValue($page, 'enabled', $storeId) && $this->isAuthorPageEnabled($page, $storeId)
                && $this->isRobotsAllowed($page, $storeId);
        } elseif ($page == 'tag') {
            return $this->isEnabled($storeId)
                && $this->getValue($page, 'enabled', $storeId)
                && $this->isRobotsAllowed($page, $storeId);
        }
        return $this->isEnabled($storeId) && $this->getValue($page, 'enabled', $storeId);
    }

    /**
     * Get sitemap frequency
     *
     * @param string $page
     * @param int|string|null $storeId
     * @return string
     */
    public function getFrequency($page, $storeId = null):string
    {
        return (string)$this->getValue($page, 'frequency', $storeId);
    }

    /**
     * Get sitemap priority
     *
     * @param string $page
     * @param int|string|null $storeId
     * @return float
     */
    public function getPriority($page, $storeId = null):float
    {
        return (float)$this->getValue($page, 'priority', $storeId);
    }

    /**
     * Get sitemap config value
     *
     * @param string $page
     * @param string $type
     * @param int|string|null $storeId
     * @return mixed
     */
    public function getValue(string $page, string $type, $storeId = null)
    {
        return $this->getConfig('mfblog/sitemap/' . $page . '/' . $type, $storeId);
    }

    /**
     * Check if author page is enabled
     *
     * @param string $page
     * @param int|string|null $storeId
     * @return mixed
     */
    public function isAuthorPageEnabled(string $page, $storeId = null)
    {
        return $this->getConfig('mfblog/' . $page . '/page_enabled', $storeId);
    }

    /**
     * Check if robots is allowed for sitemap page
     *
     * @param string $page
     * @param int|string|null $storeId
     * @return mixed
     */
    public function isRobotsAllowed(string $page, $storeId = null): bool
    {
        $allowedRobots = $this->getConfig('mfblog/' . $page . '/robots', $storeId);
        return $allowedRobots == 'INDEX,FOLLOW' || $allowedRobots == 'INDEX,NOFOLLOW';
    }
}
