<?php
namespace Kdi\Testimonials\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

class Router implements RouterInterface
{
    /**
     * @var ActionFactory
     */
    protected $actionFactory;

    public function __construct(
        ActionFactory $actionFactory
    ) {
        $this->actionFactory = $actionFactory;
    }

    public function match(RequestInterface $request)
    {

        
        /**
         * 🔒 Prevent infinite loop
         */
        if ($request->getParam('podcast_router_done')) {
            return null;
        }

        $identifier = trim($request->getPathInfo(), '/');

        if (!$identifier) {
            return null;
        }

        $parts = explode('/', $identifier);

        /**
         * Require at least 3 parts
         * /podcast/{type}/{slug}
         */
        if (count($parts) < 3 || $parts[0] !== 'successstory') {
            return null;
        }

        /**
         * Route mapping config
         */
        $routes = [
            'post' => [
                'controller' => 'post',
                'action'     => 'view'
            ]
        ];

        $type = $parts[1];
        $slug = $parts[2];

        /**
         * If route not defined → skip
         */
        if (!isset($routes[$type])) {
            return null;
        }

        $route = $routes[$type];

        /**
         * Mark router matched
         */
        $request->setParam('podcast_router_done', 1);

        /**
         * Set request params
         */
        $request->setRouteName('successstory')
            ->setModuleName('successstory')
            ->setControllerName($route['controller'])
            ->setActionName($route['action'])
            ->setParam('url', $slug);

        return $this->actionFactory->create(
            \Magento\Framework\App\Action\Forward::class
        );
    }
}
