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


namespace Mirasvit\Core\Api;

interface UrlRewriteHelperInterface
{
    /**
     * Enable/disable rewrites for module
     *
     * @param string $module    module alias (kbase)
     * @param bool   $isEnabled enable or disable
     * @return $this
     */
    public function setRewriteMode($module, $isEnabled);

    /**
     * Register base path for module
     *
     * @param string $module module alias (kbase)
     * @param string $path   base path (knowledge-base)
     * @return $this
     */
    public function registerBasePath($module, $path);

    /**
     * Register another store view's base path as an alias for the module.
     *
     * A request that carries an alias base path (e.g. after a store/language
     * switch) is redirected to the current store's registered base path instead
     * of returning a 404.
     *
     * @param string $module module alias (kbase)
     * @param string $path   another store view's base path (help, hilfe)
     * @return $this
     */
    public function registerBasePathAlias($module, string $path);

    /**
     * Register new path for module
     *
     * @param string $module   module alias (kbase)
     * @param string $type     path type (article)
     * @param string $template path template ([category_key]/[article_key])
     * @param string $action   controller action (kbase_article_view)
     * @param array  $params   additional params
     * @return $this
     */
    public function registerPath($module, $type, $template, $action, $params = []);

    /**
     * Return absolute url for entity
     *
     * @param string $module module alias (kbase)
     * @param string $type   path type (article)
     * @param \Magento\Framework\DataObject|null $entity entity
     * @return string absolute url for entity
     */
    public function getUrl($module, $type, $entity = null);

    /**
     * Normalize given string.
     * Example: ü -> ue.
     *
     * @param string $string
     * @return string
     */
    public function normalize($string);

    /**
     * Update url rewrite
     *
     * @param string $module
     * @param string $type
     * @param \Magento\Framework\DataObject $entity
     * @param array  $values
     * @param int    $storeId
     * @return bool
     */
    public function updateUrlRewrite($module, $type, $entity, $values, $storeId);

    /**
     * Delete url rewrite
     *
     * @param string $module
     * @param string $type
     * @param \Magento\Framework\DataObject $entity
     * @return bool
     */
    public function deleteUrlRewrite($module, $type, $entity);

    /**
     * Math path
     *
     * @param string $pathInfo
     * @return \Magento\Framework\DataObject|false
     */
    public function match($pathInfo);
}
