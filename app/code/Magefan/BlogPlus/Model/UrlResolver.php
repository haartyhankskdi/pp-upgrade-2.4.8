<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Model;

use Magefan\Blog\Model\Url;

/**
 * Class Blog Plus Url Resolver
 */
class UrlResolver extends \Magefan\Blog\Model\UrlResolver
{
    /**
     * Resolves the given path to determine its corresponding resource and parameters.
     *
     * @param string $path
     * @return array|null
     */
    // phpcs:ignore Generic.Metrics.NestingLevel
    public function resolve($path)
    {
        if (!$this->url->isAdvancedPermalinkEnabled()) {
            return parent::resolve($path);
        }

        $page = null;
        $params = [];
        $pathInfo = explode('/', $path);
        if ($this->config->getPagePaginationType() === '2') {
            $page = (int)$pathInfo[count($pathInfo) - 1];
            if ($page && 'page' === $pathInfo[count($pathInfo) - 2]) {
                $params['page'] = $page;
                $path = str_replace('/page/' . $page, '', $path);
            }
        }

        $identifier = trim($path, '/');
        $identifier = urldecode($identifier);
        $identifierLen = strlen($identifier);

        $basePath = trim($this->url->getBasePath(), '/');

        if ($identifier == $basePath) {
            return ['id' => 1, 'params' => $params, 'type' => Url::CONTROLLER_INDEX];
        } else {
            $schemas = $this->url->getPathChemas();
            foreach ($schemas as $controllerName => $schema) {
                $schema = (string)$schema;
                $schema = trim($schema, '/');
                $startVar = strpos($schema, '{');
                $endVar = strrpos($schema, '}');

                if (false === $startVar || false === $endVar) {
                    continue;
                }

                if (substr($schema, 0, $startVar) != substr($identifier, 0, $startVar)) {
                    continue;
                }

                $endVar++;
                if (substr($schema, $endVar) != substr($identifier, $identifierLen - (strlen($schema) - $endVar))) {
                    continue;
                }

                $subSchema = substr($schema, $startVar, $endVar - $startVar);
                $subIdentifier = substr(
                    $identifier,
                    $startVar,
                    $identifierLen - (strlen($schema) - $endVar) - $startVar
                );

                $pathInfo = explode('/', $subIdentifier);

                $subSchema = explode('/', $subSchema);
                if (($subSchema[0] == '{{blog_route}}') && (strpos($subIdentifier, $basePath) === false)) {
                    continue;
                }

                if ('{' != $subSchema[0][0] && $subSchema[0] != $pathInfo[0]) {
                    continue;
                }

                if ($subSchema[count($subSchema) - 1] == '{{url_key}}') {
                    switch ($controllerName) {
                        case 'post':
                        case 'category':
                        case 'tag':
                        case 'author':
                            $method = '_get' . ucfirst($controllerName) . 'Id';

                            $id = null;

                            $pathInfoCount = count($pathInfo);
                            $j = $pathInfoCount;
                            while (!$id && $j > 0) {
                                $id = $this->$method(
                                    implode(
                                        '/',
                                        array_slice($pathInfo, $pathInfoCount - $j, $j)
                                    )
                                );

                                $j--;
                            }

                            if ($id) {
                                $factory = $controllerName . 'Factory';
                                $model = $this->$factory->create()->setStoreId($this->getStoreId())->load($id);
                                if ($model->getId()) {
                                    $parentCategories = $model->getParentCategories();
                                    /* Fix for post that assigned to different categories */
                                    $hasCategory = false;
                                    if ($parentCategories
                                        && (is_array($parentCategories) || $parentCategories instanceof \Countable)
                                        && count($parentCategories)
                                    ) {
                                        foreach ($parentCategories as $category) {
                                            if ($category->isVisibleOnStore($this->getStoreId())) {

                                                $hasCategory = true;
                                                $model->setData('parent_category', $category);

                                                $path = $this->url->getUrlPath($model, $controllerName);
                                                $path = trim($path, '/');

                                                if ($path == $identifier) {
                                                    $result = ['id' => $id, 'type' => $controllerName];

                                                    // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                                                    $result['params'] = array_merge($params, [
                                                        'category_id' => $category->getId(),
                                                    ]);

                                                    return $result;
                                                }
                                            }
                                        }
                                    }

                                    if (!$hasCategory) {
                                        $path = $this->url->getUrlPath($model, $controllerName);
                                        $path = trim($path, '/');

                                        if ($path == $identifier) {
                                            return ['id' => $id, 'params' => $params, 'type' => $controllerName];
                                        }
                                    }
                                }
                            }
                            break;
                        case 'archive':
                            $date = $pathInfo[count($pathInfo) - 1];
                            if ($this->_isArchiveIdentifier($date)) {
                                $path = $this->url->getUrlPath($date, $controllerName);
                                $path = trim($path, '/');
                                if ($path == $identifier) {
                                    return ['id' => $date, 'params' => $params, 'type' =>$controllerName];
                                }
                            }
                            break;
                        case 'search':
                            $q = '';
                            for ($x = 1; $x <=4; $x++) {
                                if (!isset($pathInfo[count($pathInfo) - $x])) {
                                    break;
                                }
                                $q = $pathInfo[count($pathInfo) - $x] . ($q ? '/' : '') . $q;
                                $path = $this->url->getUrlPath($q, $controllerName);
                                $path = trim($path, '/');
                                if ($path == $identifier) {
                                    return ['id' => $q, 'params' => $params, 'type' => $controllerName];
                                }
                            }
                            break;
                        default:
                            /* do nothing */
                    }
                }
            }
        }
    }
}
