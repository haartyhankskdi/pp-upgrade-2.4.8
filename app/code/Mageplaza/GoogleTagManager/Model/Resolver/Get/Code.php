<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

declare(strict_types=1);

namespace Mageplaza\GoogleTagManager\Model\Resolver\Get;

use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Mageplaza\GoogleTagManager\Helper\Data;
use Mageplaza\GoogleTagManager\Model\CodeRepository;

/**
 * Class Code
 * @package Mageplaza\GoogleTagManager\Model\Resolver\Get
 */
class Code implements ResolverInterface
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var CodeRepository
     */
    protected $codeRepository;

    /**
     * @var ProductQueryInterface
     */
    protected $searchQuery;

    /**
     * Code constructor.
     *
     * @param Data $helperData
     * @param CodeRepository $codeRepository
     * @param ProductQueryInterface $searchQuery
     */
    public function __construct(
        Data $helperData,
        CodeRepository $codeRepository,
        ProductQueryInterface $searchQuery
    ) {
        $this->helperData     = $helperData;
        $this->codeRepository = $codeRepository;
        $this->searchQuery    = $searchQuery;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     *
     * @return array|Value|mixed
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->helperData->isEnabled()) {
            throw new GraphQlInputException(__('The module is disabled'));
        }

        if (!$args) {
            return ['code' => $this->codeRepository->getGTMCodeHome()];
        }

        $filters = $args['filters'];

        if (in_array($filters['action'], ['category', 'catalogsearch'], true)) {
            $isCategory = $filters['action'] === 'category';

            if ($isCategory) {
                $filter['filter'] = [
                    'category_id' => [
                        'eq' => $filters['id']
                    ]
                ];
            } else {
                $filter['search'] = $filters['id'];
            }

            $filter['pageSize']    = $filters['pageSize'] ?: 20;
            $filter['currentPage'] = $filters['currentPage'] ?: 1;
            $filter['sort']        = $filters['sort'];
            $searchResult          = $this->searchQuery->getResult($filter, $info, $context);

            if ($searchResult->getCurrentPage() > $searchResult->getTotalPages()
                && $searchResult->getTotalCount() > 0) {
                throw new GraphQlInputException(
                    __(
                        'currentPage value %1 specified is greater than the %2 page(s) available.',
                        [$searchResult->getCurrentPage(), $searchResult->getTotalPages()]
                    )
                );
            }

            $items = $searchResult->getProductsSearchResult();
            $code  = $isCategory
                ? $this->codeRepository->getCodeCategoryDataGraphQl($filters['type'], $items, $filters['id'])
                : $this->codeRepository->getCodeSearchDataGraphQl($filters['type'], $items);

            return ['code' => $code];
        }

        $code = $this->codeRepository->getCodeGraphQl($filters['type'], $filters['action'], $filters['id']);

        return ['code' => $code];
    }
}
