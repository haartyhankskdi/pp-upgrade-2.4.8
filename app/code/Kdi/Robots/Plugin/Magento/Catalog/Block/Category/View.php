<?php
/**
 * Plugin for modifying category view page behavior in Magento 2.
 *
 * @category Magento2
 * @package  Kdi_Robots
 * @copyright Copyright © no All rights reserved.
 * @license See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Robots\Plugin\Magento\Catalog\Block\Category;

use Magento\Catalog\Block\Category\View as CategoryView;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Catalog\Model\CategoryRepository;

class View
{
    /**
     * @var PageConfig
     */
    protected $pageConfig;

    /**
     * @var LayerResolver
     */
    protected $layerResolver;

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * Constructor.
     *
     * @param PageConfig $pageConfig
     * @param LayerResolver $layerResolver
     * @param HttpRequest $request
     * @param EavConfig $eavConfig
     * @param AttributeRepositoryInterface $attributeRepository
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        PageConfig $pageConfig,
        LayerResolver $layerResolver,
        HttpRequest $request,
        EavConfig $eavConfig,
        AttributeRepositoryInterface $attributeRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->pageConfig = $pageConfig;
        $this->layerResolver = $layerResolver;
        $this->request = $request;
        $this->eavConfig = $eavConfig;
        $this->attributeRepository = $attributeRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Plugin after `setLayout` for Category View block.
     *
     * @param CategoryView $subject
     * @param mixed $result
     * @param mixed $layout
     * @return mixed
     */
    public function afterSetLayout(CategoryView $subject, $result, $layout)
    {
        $params = $this->request->getParams();

        // Remove redundant query parameters
        unset($params['_']);

        // Apply robots metadata if specific conditions are met
        if (count($params) === 2 && isset($params['price'])) {
            $this->pageConfig->setMetadata('robots', 'NOINDEX,FOLLOW');
        }

        return $result;
    }
}
