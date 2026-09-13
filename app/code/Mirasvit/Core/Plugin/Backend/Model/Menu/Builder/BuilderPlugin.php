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



namespace Mirasvit\Core\Plugin\Backend\Model\Menu\Builder;

use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Item;
use Magento\Backend\Model\Menu\ItemFactory;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Route\ConfigInterface as RouteConfigInterface;
use Mirasvit\Core\Block\Adminhtml\Menu as MenuBlock;
use Mirasvit\Core\Model\Config;
use Mirasvit\Core\Service\CompatibilityService;
use Mirasvit\Core\Service\PackageService;

class BuilderPlugin
{
    /** @var Config */
    private $config;

    /** @var ItemFactory */
    private $itemFactory;

    /** @var PackageService */
    private $packageService;

    /** @var MenuBlock */
    private $menuBlock;

    /** @var UrlInterface */
    private $urlManager;

    private $routeConfig;

    public function __construct(
        Config               $config,
        ItemFactory          $itemFactory,
        PackageService       $packageService,
        MenuBlock            $menuBlock,
        UrlInterface         $urlManager,
        RouteConfigInterface $routeConfig
    ) {
        $this->config         = $config;
        $this->itemFactory    = $itemFactory;
        $this->packageService = $packageService;
        $this->menuBlock      = $menuBlock;
        $this->urlManager     = $urlManager;
        $this->routeConfig    = $routeConfig;
    }

    /**
     * @param mixed $subject
     * @param Menu  $menu
     *
     * @return Menu
     */
    public function afterGetResult($subject, Menu $menu)
    {
        if (!$this->config->isMenuEnabled()
            || CompatibilityService::is20()
            || CompatibilityService::is21()
            || CompatibilityService::isMarketplace()
        ) {
            return $this->removeMenu($menu);
        }

        $moduleItems = [];

        foreach ($this->packageService->getPackageList() as $package) {
            foreach ($package->getModuleList() as $moduleName) {
                if ($moduleName === 'Mirasvit_Core') {
                    continue;
                }

                $group = $package->getLabel();

                if (!$group) {
                    $group = (string)__('Other');
                }

                switch ($moduleName) {
                    case 'Mirasvit_Report':
                    case 'Mirasvit_Dashboard':
                    case 'Mirasvit_ReportBuilder':
                        $group = (string)__('Advanced Reports');
                        break;
                    case 'Mirasvit_LandingPage':
                        $group = (string)__('Layered Navigation');
                        break;
                }

                if (!isset($moduleItems[$group])) {
                    $moduleItems[$group] = [];
                }

                $nativeMenuItems = $this->filterItems($menu, $moduleName);

                foreach ($nativeMenuItems as $idx => $item) {
                    $data = $item->toArray();
                    unset($data['sub_menu']);

                    if (!$data['action']) {
                        continue;
                    }

                    // a menu.xml may declare the action with a route front name instead of the
                    // route id - keep those out of the menu we build
                    $data['action'] = $this->normalizeRouteId((string)$data['action']);

                    $url    = $this->urlManager->getUrl($data['action']);
                    $urlKey = $this->normalizeUrlKey($url);

                    $moduleItems[$group][$urlKey] = $data;
                }

                $items = $this->menuBlock->getItemsByModuleName($moduleName);
                foreach ($items as $idx => $item) {
                    if (!is_object($item)) {
                        continue;
                    }

                    $action = $this->resolveRoutePath((string)$item->getUrl());

                    $urlKey = $this->normalizeUrlKey($item->getData('url'));

                    // a menu.xml declaration for the same target already carries
                    // a route path and an id we can rely on
                    $native = $moduleItems[$group][$urlKey] ?? null;

                    $title = (string)$item->getData('title');

                    if (strlen($title) > 50) {
                        $title = substr($title, 0, 47) . '...';
                    }

                    $moduleItems[$group][$urlKey] = [
                        'id'       => $native['id'] ?? null,
                        'module'   => $moduleName,
                        'resource' => $item->getData('resource'),
                        'title'    => $title,
                    ];

                    // need this for external links
                    if (preg_match('/^https?:/', $action)) {
                        $moduleItems[$group][$urlKey]['path'] = $item->getData('url');
                    } else {
                        $moduleItems[$group][$urlKey]['action'] = $native['action'] ?? $action;
                    }
                }
            }
        }

        $moduleItems = $this->addModuleComment($moduleItems);

        ksort($moduleItems);

        $moduleItems = $this->moveDownNavGetSupport($moduleItems);

        $filteredItems = [];

        foreach ($moduleItems as $group => $items) {
            if ($items) {
                $filteredItems[$group] = $items;
            }
        }

        if (count($filteredItems) <= 1) {
            return $this->removeMenu($menu);
        }

        foreach ($filteredItems as $group => $items) {
            $moduleData = [
                'title'    => $group,
                'id'       => hash('sha256', $group),
                'resource' => 'Mirasvit_Core::menu',
            ];

            foreach ($items as $item) {
                $item['id'] = 'Mirasvit_Core::menu::' . $this->buildItemKey($item);

                $moduleData['sub_menu'][] = $item;
            }
            $moduleItem = $this->itemFactory->create([
                'data' => $moduleData,
            ]);

            $menu->add($moduleItem, 'Mirasvit_Core::menu');
        }

        return $menu;
    }

    /**
     * Convert a generated admin url into a route path usable as a menu item action.
     *
     * The first segment of an action must be the route ID, not its front name. Magento salts the
     * admin secret key with the route name, so an action built from a front name ("admin" instead
     * of "adminhtml") produces urls whose key never validates - and since an invalid key redirects
     * to the startup page, such an item chosen as the startup page loops the admin forever.
     *
     * @param string $url
     *
     * @return string
     */
    private function resolveRoutePath(string $url): string
    {
        $path = preg_replace('/\/key\/.*/', '', $url);
        $path = str_replace($this->urlManager->getBaseUrl(), '', (string)$path);

        if (preg_match('/^https?:/', $path)) {
            return $path;
        }

        $path          = ltrim($path, '/');
        $areaFrontName = $this->urlManager->getAreaFrontName();

        if ($areaFrontName && strpos($path, $areaFrontName . '/') === 0) {
            $path = substr($path, strlen($areaFrontName) + 1);
        }

        return $this->normalizeRouteId($path);
    }

    /**
     * Replace a leading route front name with the route id it belongs to.
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizeRouteId(string $path): string
    {
        if ($path === '' || preg_match('/^https?:/', $path)) {
            return $path;
        }

        $parts   = explode('/', $path);
        $routeId = $this->routeConfig->getRouteByFrontName($parts[0]);

        if ($routeId) {
            $parts[0] = $routeId;
        }

        return implode('/', $parts);
    }

    /**
     * Stable, unique suffix for a generated menu item id.
     *
     * Item ids are persisted by merchants (Stores > Configuration > Advanced > Admin > Startup
     * Page stores the menu item id), so they must not depend on how many other Mirasvit
     * extensions happen to be installed.
     *
     * @param array $item
     *
     * @return string
     */
    private function buildItemKey(array $item): string
    {
        if (!empty($item['id']) && !preg_match('/^https?:/', (string)$item['id'])) {
            return (string)$item['id'];
        }

        $target = $item['action'] ?? $item['path'] ?? '';

        return ($item['module'] ?? 'Mirasvit_Core') . '::' . sha1($this->normalizeUrlKey((string)$target));
    }

    private function moveDownNavGetSupport(array $moduleItems): array
    {
        if (isset($moduleItems['Layered Navigation']) && isset($moduleItems['Layered Navigation']['https://mirasvit.com/support'])) {
            $getSupport = $moduleItems['Layered Navigation']['https://mirasvit.com/support'];
            unset($moduleItems['Layered Navigation']['https://mirasvit.com/support']);
            array_push($moduleItems['Layered Navigation'], $getSupport);
        }

        return $moduleItems;
    }

    /**
     * @param Menu   $menu
     * @param string $moduleName
     *
     * @return Item[]
     */
    private function filterItems(Menu $menu, $moduleName)
    {
        $items = [];

        /** @var Item $item */
        foreach ($menu->getIterator() as $item) {
            $id = $item->getId();

            if (strpos($id, $moduleName) !== false) {
                $items[] = $item;
            }

            if ($item->hasChildren()) {
                $items = array_merge($items, $this->filterItems($item->getChildren(), $moduleName));
            }
        }

        return $items;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function normalizeUrlKey($url)
    {
        $url = str_replace('/index/', '/', $url);
        $url = rtrim($url, '/');

        return $url;
    }

    /**
     * @param Menu $menu
     *
     * @return Menu
     */
    private function removeMenu(Menu $menu)
    {
        $menu->remove('Mirasvit_Core::menu');

        return $menu;
    }

    private function addModuleComment(array $moduleItems): array
    {
        $other = $moduleItems['Other'] ?? [];
        foreach ($other as $key => $item) {
            $action = $item['action'] ?? '';
            if ($action == 'mst_comment/comment') {
                $blogMx = $moduleItems['Blog MX'] ?? [];
                if (count($blogMx) > 0) {
                    $moduleItems['Blog MX'][$key] = [
                        'id'       => $key,
                        'module'   => 'Mirasvit_BlogMx',
                        'resource' => 'Mirasvit_BlogMx::blog',
                        'title'    => $item['title'] ?? '',
                        'action'   => $action,
                    ];
                }

                $review = $moduleItems['Advanced Reviews'] ?? [];
                if (count($review) > 0) {
                    $moduleItems['Advanced Reviews'][$key] = [
                        'id'       => $key,
                        'module'   => 'Mirasvit_Review',
                        'resource' => 'Mirasvit_Review::review',
                        'title'    => $item['title'] ?? '',
                        'action'   => $action,
                    ];
                }

                unset($other[$key]);
                break;
            }
        }

        if (count($other) == 0) {
            unset($moduleItems['Other']);
        }

        $moduleItems = $this->positionModuleComment($moduleItems);

        return $moduleItems;
    }

    private function positionModuleComment(array $moduleItems): array
    {
        if (count($moduleItems['Blog MX'] ?? [])) {
            foreach ($moduleItems['Blog MX'] as $key => $item) {
                if ($item['resource'] == 'Mirasvit_BlogMx::blog_settings') {
                    unset($moduleItems['Blog MX'][$key]);
                    $moduleItems['Blog MX'][$key] = $item;
                    break;
                }
            }
        }

        if (count($moduleItems['Advanced Reviews'] ?? [])) {
            foreach ($moduleItems['Advanced Reviews'] as $key => $item) {
                if ($item['resource'] == 'Mirasvit_Review::review_settings') {
                    unset($moduleItems['Advanced Reviews'][$key]);
                    $moduleItems['Advanced Reviews'][$key] = $item;
                    break;
                }
            }
        }

        return $moduleItems;
    }

}
