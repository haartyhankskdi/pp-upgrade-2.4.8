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



namespace Mirasvit\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Filter\FilterManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Core\Api\UrlRewriteHelperInterface;
use Mirasvit\Core\Model\ResourceModel\UrlRewrite\CollectionFactory as UrlRewriteCollectionFactory;
use Mirasvit\Core\Model\UrlRewriteFactory;

class UrlRewrite extends AbstractHelper implements UrlRewriteHelperInterface
{
    /**
     * @var UrlRewriteFactory
     */
    protected $urlRewriteFactory;

    /**
     * @var UrlRewriteCollectionFactory
     */
    protected $urlRewriteCollectionFactory;

    /**
     * @var FilterManager
     */
    protected $filter;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $configB = [];

    /**
     * Base paths registered for other store views, mapped to the module they
     * belong to. Used to redirect a request that carries another store's base
     * path (e.g. after a store/language switch) to the current store's base.
     *
     * @var array
     */
    protected $configAlias = [];

    /**
     * UrlRewrite constructor.
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param UrlRewriteCollectionFactory $urlRewriteCollectionFactory
     * @param FilterManager $filter
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     */
    /**
     * Url keys already read this request, keyed "module|type|entityId|storeId"; null means "no rewrite
     * for this entity", which is an answer worth remembering too. See preload() and cachedUrlKey().
     *
     * @var array<string, string|null>
     */
    private $urlKeys = [];

    public function __construct(
        UrlRewriteFactory $urlRewriteFactory,
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        FilterManager $filter,
        StoreManagerInterface $storeManager,
        Context $context
    ) {
        $this->urlRewriteFactory           = $urlRewriteFactory;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
        $this->filter                      = $filter;
        $this->storeManager                = $storeManager;
        $this->urlManager                  = $context->getUrlBuilder();
        $this->scopeConfig                 = $context->getScopeConfig();

        parent::__construct($context);
    }

    /**
     * Is enabled rewrites for module?
     *
     * @param string $module module alias (kbase)
     *
     * @return bool
     */
    public function isEnabled($module)
    {
        if (!isset($this->config[$module])) {
            return false;
        }
        if (isset($this->config[$module]['_ENABLED'])) {
            return $this->config[$module]['_ENABLED'];
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setRewriteMode($module, $isEnabled)
    {
        $this->config[$module]['_ENABLED'] = $isEnabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBasePath($module, $path)
    {
        $this->config[$module]['_BASE_PATH'] = $path;
        $this->configB[$path]                = $module;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBasePathAlias($module, string $path)
    {
        // Never shadow the current store's own base path, and ignore empty values. $path is a
        // native string param so a null (a contract violation) fails at the call boundary rather
        // than silently registering an alias under an empty key.
        if ($path !== '' && !isset($this->configB[$path])) {
            $this->configAlias[$path] = $module;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function registerPath($module, $type, $template, $action, $params = [])
    {
        $this->config[$module][$type]                   = $template;
        $this->configB[$module . '_' . $type]['ACTION'] = $action;
        $this->configB[$module . '_' . $type]['PARAMS'] = $params;

        return $this;
    }

    /**
     * @param string $module
     * @param string $type
     * @param \Magento\Framework\DataObject|null $entity
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUrl($module, $type, $entity = null)
    {
        if ($this->isEnabled($module)) {
            $basePath = $this->config[$module]['_BASE_PATH'];

            if ($entity) {
                // ONE READ PER ENTITY PER REQUEST, and none at all for an entity preload() already
                // covered. Measured with the page profiler on a 2.4.7 store: a knowledge-base article
                // page called this FOUR times for two entities (the article and its category, each asked
                // for by the breadcrumbs, the canonical tag and the body), and a listing of twenty
                // articles issued twenty-one selects - one per row, which is what preload() exists to
                // collapse into one.
                $urlKey = $this->cachedUrlKey($module, $type, (int)$entity->getId());

                if ($urlKey !== null) {
                    return $this->getUrlByKey($basePath, $urlKey);
                }

                return $this->getDefaultUrl($module, $type, $entity);
            } else {
                return $this->getUrlByKey($basePath, $this->config[$module][$type]);
            }
        } else {
            return $this->getDefaultUrl($module, $type, $entity);
        }
    }

    /**
     * Load the url keys for a whole page of entities in one select.
     *
     * A listing renders one link per row, and getUrl() can only ever answer about the entity in front of
     * it - so without this a page of N rows costs N selects. Call it once with the ids on the page (see
     * Mirasvit\Kb\Block\Article\ListArticle for a caller) and every getUrl() below is answered from
     * memory.
     *
     * Bounded on purpose: it takes the ids to load rather than reading the whole module/type set, so a
     * store with fifty thousand articles still loads only the page in front of the visitor.
     *
     * @param string $module
     * @param string $type
     * @param int[]  $entityIds
     */
    public function preload($module, $type, array $entityIds): void
    {
        if (!$this->isEnabled($module)) {
            return;
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $missing = [];

        foreach ($entityIds as $entityId) {
            $entityId = (int)$entityId;
            if (!array_key_exists($this->urlKeyCacheKey($module, $type, $entityId, $storeId), $this->urlKeys)) {
                $missing[$entityId] = $entityId;
            }
        }

        if (!$missing) {
            return;
        }

        $collection = $this->urlRewriteCollectionFactory->create()
            ->addFieldToFilter('module', $module)
            ->addFieldToFilter('type', $type)
            ->addFieldToFilter('entity_id', ['in' => array_values($missing)])
            ->addFieldToFilter('store_id', ['in' => [0, $storeId]]);

        // Every requested id is recorded, INCLUDING the ones with no rewrite - as null, so a later
        // getUrl() for them falls through to the default url without going back to the database. That is
        // the whole point: a page of articles that have no rewrites yet must not cost one select each.
        foreach ($missing as $entityId) {
            $this->urlKeys[$this->urlKeyCacheKey($module, $type, $entityId, $storeId)] = null;
        }

        /** @var \Mirasvit\Core\Model\UrlRewrite $rewrite */
        foreach ($collection as $rewrite) {
            $key = $this->urlKeyCacheKey($module, $type, (int)$rewrite->getEntityId(), $storeId);

            // getFirstItem() semantics preserved: the first row wins, later ones are ignored.
            if ($this->urlKeys[$key] === null) {
                $this->urlKeys[$key] = $rewrite->getUrlKey();
            }
        }
    }

    /**
     * Forget everything preload() and getUrl() have cached.
     *
     * updateUrlRewrite() and deleteUrlRewrite() call this, because a CLI process (the url-rewrite
     * regeneration command, a data patch, an import) writes and reads in the SAME process, where a
     * request-lifetime cache would otherwise keep handing out the pre-write key.
     */
    public function forgetCachedUrlKeys(): void
    {
        $this->urlKeys = [];
    }

    /**
     * The url key for one entity, or null when it has no rewrite. Reads the cache, filling it on a miss.
     *
     * @param string $module
     * @param string $type
     * @param int    $entityId
     *
     * @return string|null
     */
    private function cachedUrlKey($module, $type, int $entityId): ?string
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $key     = $this->urlKeyCacheKey($module, $type, $entityId, $storeId);

        if (array_key_exists($key, $this->urlKeys)) {
            return $this->urlKeys[$key];
        }

        $collection = $this->urlRewriteCollectionFactory->create()
            ->addFieldToFilter('module', $module)
            ->addFieldToFilter('type', $type)
            ->addFieldToFilter('entity_id', ['eq' => $entityId])
            ->addFieldToFilter('store_id', ['in' => [0, $storeId]]);

        if ($collection->count()) {
            /** @var \Mirasvit\Core\Model\UrlRewrite $rewrite */
            $rewrite = $collection->getFirstItem();

            return $this->urlKeys[$key] = $rewrite->getUrlKey();
        }

        return $this->urlKeys[$key] = null;
    }

    /**
     * @param string $module
     * @param string $type
     * @param int    $entityId
     * @param int    $storeId
     *
     * @return string
     */
    private function urlKeyCacheKey($module, $type, int $entityId, int $storeId): string
    {
        return $module . '|' . $type . '|' . $entityId . '|' . $storeId;
    }

    /**
     * Return unique path (recursive check)
     *
     * @param string $module module alias (kbase)
     * @param string $type path type (category, article etc)
     * @param string $path path url key
     * @param string $entityId entity id
     * @param int    $storeId store id
     * @param array  $i additional
     *
     * @return string
     */
    protected function getUniquePath($module, $type, $path, $entityId, $storeId, $i = [])
    {
        if (isset($i[$storeId])) {
            $pathToCheck = $path . '-' . $i[$storeId];
        } else {
            $i[$storeId] = 0;
            $pathToCheck = $path;
        }

        // check path for duplicates
        $collection = $this->urlRewriteCollectionFactory->create()
            ->addFieldToFilter('module', $module)
            ->addFieldToFilter('type', $type)
            ->addFieldToFilter('url_key', $pathToCheck)
            ->addFieldToFilter('entity_id', ['neq' => $entityId])
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->setOrder('url_key', 'asc');

        if ($collection->count()) {
            ++$i[$storeId];

            return $this->getUniquePath($module, $type, $path, $entityId, $storeId, $i);
        }

        return $pathToCheck;
    }

    /**
     * {@inheritdoc}
     */
    public function updateUrlRewrite($module, $type, $entity, $values, $storeId)
    {
        $this->forgetCachedUrlKeys();

        if (!isset($this->config[$module])) {
            return false;
        }

        $objectId     = $entity->getId();
        $pathTemplate = $this->config[$module][$type];
        $path         = $pathTemplate;

        foreach ($values as $key => $value) {
            $path = str_replace("[$key]", $value, $path);
        }

        $path = trim($path, '/');
        $path = $this->getUniquePath($module, $type, $path, $objectId, $storeId);

        $collection = $this->urlRewriteCollectionFactory->create()
            ->addFieldToFilter('module', $module)
            ->addFieldToFilter('type', $type)
            ->addFieldToFilter('entity_id', $objectId)
            ->addFieldToFilter('store_id', ['eq' => $storeId]);
        if ($collection->count()) {
            /** @var \Mirasvit\Core\Model\UrlRewrite $rewrite */
            $rewrite = $collection->getFirstItem();
            $rewrite->setUrlKey($path)
                ->setStoreId($storeId)//compatibility with old versions
                ->save();
        } else {
            $rewrite = $this->urlRewriteFactory->create();
            $rewrite
                ->setModule($module)
                ->setType($type)
                ->setEntityId($objectId)
                ->setUrlKey($path)
                ->setStoreId($storeId)
                ->save();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUrlRewrite($module, $type, $entity)
    {
        $this->forgetCachedUrlKeys();

        $collection = $this->urlRewriteCollectionFactory->create()
            ->addFieldToFilter('module', $module)
            ->addFieldToFilter('type', $type)
            ->addFieldToFilter('entity_id', $entity->getId());
        if ($collection->count()) {
            /** @var \Mirasvit\Core\Model\UrlRewrite $rewrite */
            $rewrite = $collection->getFirstItem();
            $rewrite->delete();
        }

        return true;
    }

    /**
     * Absolute default url
     *
     * @param string $module
     * @param string $type
     * @param \Magento\Framework\DataObject|null $object
     *
     * @return string
     */
    protected function getDefaultUrl($module, $type, $object)
    {
        if (!isset($this->configB[$module . '_' . $type])) {
            return '';
        }

        $action = $this->configB[$module . '_' . $type]['ACTION'];
        $params = $this->configB[$module . '_' . $type]['PARAMS'];

        $action = str_replace('_', '/', $action);
        if ($object) {
            $params['id'] = $object->getId();
        }

        return $this->urlManager->getUrl($action, $params);
    }

    /**
     * Absolute url by key
     *
     * @param string     $basePath
     * @param string     $urlKey
     * @param array      $params
     *
     * @return string
     */
    protected function getUrlByKey($basePath, $urlKey, $params = [])
    {
        if ($urlKey) {
            $url = $basePath . '/' . $urlKey;
        } else {
            $url = $basePath;
        }
        $configUrlSuffix = (string) $this->getProductUrlSuffix();
        //user can enter .html or html suffix
        if ($configUrlSuffix != '' && $configUrlSuffix != '/' && $configUrlSuffix[0] != '.') {
            $configUrlSuffix = '.' . $configUrlSuffix;
        }
        if (substr($url, -strlen($configUrlSuffix)) == $configUrlSuffix) {
            $url = substr($url, 0, -strlen($configUrlSuffix));
        }
        $url .= $configUrlSuffix;
        $url = $this->urlManager->getDirectUrl($url);

        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Return url without suffix
     *
     * @param string $key
     *
     * @return string
     */
    protected function getUrlKeyWithoutSuffix($key)
    {
        $configUrlSuffix = $this->getProductUrlSuffix();

        //user can enter .html or html suffix
        if ($configUrlSuffix != '' && $configUrlSuffix != '/' && $configUrlSuffix[0] != '.') {
            $configUrlSuffix = '.' . $configUrlSuffix;
        }

        if ($configUrlSuffix != '/' && $configUrlSuffix !== $key) {
            if(!empty($key) && !empty($configUrlSuffix)) {
                $key = str_replace($configUrlSuffix, '', $key);
            }
        }

        return $key;
    }

    /**
     * Build a redirect url that swaps a foreign store's base path (the leading
     * request segment) for the current store's registered base path, keeping the
     * rest of the request path and the store code prefix intact.
     *
     * @param string $module module the alias base path belongs to
     * @param array  $parts  request path parts; $parts[0] is the foreign base path
     *
     * @return string|null redirect url, or null when the module has no usable base path
     */
    private function getBasePathForwardUrl($module, array $parts)
    {
        if (!$this->isEnabled($module) || empty($this->config[$module]['_BASE_PATH'])) {
            return null;
        }

        $parts[0] = $this->config[$module]['_BASE_PATH'];

        $isStoreCodeIncluded = $this->scopeConfig->getValue(
            'web/url/use_store',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getCode()
        );
        $storeCode = $isStoreCodeIncluded ? '/' . $this->storeManager->getStore()->getCode() : '';

        return $storeCode . '/' . implode('/', $parts);
    }

    /**
     * Math path
     *
     * @param string $pathInfo
     *
     * @return bool|DataObject
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function match($pathInfo)
    {
        $identifier = trim($pathInfo, '/');
        $parts      = explode('/', $identifier);

        if (count($parts) == 1) {
            $parts[0] = $this->getUrlKeyWithoutSuffix($parts[0]);
        }

        if (!isset($this->configB[$parts[0]])) {
            // The leading segment is not this store's base path. If it is a base
            // path registered for another store view (e.g. carried over by a
            // store/language switch that stripped the store code), redirect to
            // this store's base path so every KB url type resolves here instead
            // of returning a 404.
            if (isset($this->configAlias[$parts[0]])) {
                $forwardUrl = $this->getBasePathForwardUrl($this->configAlias[$parts[0]], $parts);
                if ($forwardUrl !== null) {
                    return new DataObject(['forwardUrl' => $forwardUrl]);
                }
            }

            return false;
        }

        $configUrlSuffix = $this->getProductUrlSuffix();

        $isStoreCodeIncluded = $this->scopeConfig->getValue(
            'web/url/use_store',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getCode());

        $storeCode = $isStoreCodeIncluded ? '/' . $this->storeManager->getStore()->getCode() : '';

        if ($configUrlSuffix && $pathInfo == $this->getUrlKeyWithoutSuffix($pathInfo)) {
            // if suffix already included in path
            if (preg_match('/' . preg_quote($configUrlSuffix, '/') . '$/i', $pathInfo)
            || ($configUrlSuffix === '/' && $this->configB[$parts[0]] == 'KBASE')) {
                $configUrlSuffix = '';
            } else {
                $result = new DataObject(['forwardUrl' => $storeCode
                    . $this->getUrlKeyWithoutSuffix($pathInfo)
                    . $configUrlSuffix]);

                return $result;
            }
        }

        if ($pathInfo != $this->getUrlKeyWithoutSuffix($pathInfo) . $configUrlSuffix) {
            return false;
        }

        $module = $this->configB[$parts[0]];

        if (!$this->isEnabled($module)) {
            return false;
        }
        if (count($parts) > 1) {
            unset($parts[0]);
            $urlKey = implode('/', $parts);
            $urlKey = urldecode($urlKey);
            $urlKey = $this->getUrlKeyWithoutSuffix($urlKey);
        } else {
            $urlKey = '';
        }

        # check on static urls (urls for static pages, ex. lists)
        $type = $rewrite = false;
        foreach ($this->config[$module] as $t => $key) {
            if ($key === $urlKey) {
                if ($t == '_BASE_PATH') {
                    continue;
                }
                $type = $t;
                break;
            }
        }

        # check on dynamic urls (ex. urls of products, categories etc)
        if (!$type) {
            $collection = $this->urlRewriteCollectionFactory->create()
                ->addFieldToFilter('url_key', $urlKey)
                ->addFieldToFilter('module', $module)
                ->addFieldToFilter('store_id', ['in' => [0, $this->storeManager->getStore()->getId()]]);
            if ($collection->count()) {
                /** @var \Mirasvit\Core\Model\UrlRewrite $rewrite */
                $rewrite = $collection->getFirstItem();
                $type    = $rewrite->getType();
            } else {
                return false;
            }
        }
        if ($type) {
            $action      = $this->configB[$module . '_' . $type]['ACTION'];
            $params      = $this->configB[$module . '_' . $type]['PARAMS'];
            $result      = new DataObject();
            $actionParts = explode('_', $action);

            $result->addData([
                'route_name'      => $actionParts[0],
                'module_name'     => $actionParts[0],
                'controller_name' => $actionParts[1],
                'action_name'     => $actionParts[2],
                'action_params'   => $params,
            ]);

            if ($rewrite) {
                $result->setData('entity_id', $rewrite->getEntityId());
            }

            return $result;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($string)
    {
        //@codingStandardsIgnoreStart
        $table = [
            'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c',
            'Ć' => 'C', 'ć' => 'c', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Å' => 'A',
            'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I',
            'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe',
            'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'Ŕ' => 'R',
            'ŕ' => 'r', 'ü' => 'ue', '/' => '', '&' => '', '(' => '', ')' => '',
        ];
        //@codingStandardsIgnoreStop

        $string = strtr($string, $table);
        $string = $this->filter->translitUrl($string);

        return $string;
    }

    /**
     * @return string
     */
    private function getProductUrlSuffix()
    {
        return (string)$this->scopeConfig->getValue('catalog/seo/product_url_suffix', ScopeInterface::SCOPE_STORE);
    }
}
