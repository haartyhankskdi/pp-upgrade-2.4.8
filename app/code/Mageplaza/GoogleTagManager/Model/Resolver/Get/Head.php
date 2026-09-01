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

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mageplaza\GoogleTagManager\Helper\Data;
use Mageplaza\GoogleTagManager\Model\CodeRepository;

/**
 * Class Head
 * @package Mageplaza\GoogleTagManager\Model\Resolver\Get
 */
class Head implements ResolverInterface
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
     * Code constructor.
     *
     * @param Data $helperData
     * @param CodeRepository $codeRepository
     */
    public function __construct(
        Data $helperData,
        CodeRepository $codeRepository
    ) {
        $this->helperData     = $helperData;
        $this->codeRepository = $codeRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->helperData->isEnabled()) {
            throw new GraphQlInputException(__('The module is disabled'));
        }

        $filters = $args['filters'];
        $head    = $this->codeRepository->getHead($filters['type']);

        return ['head' => $head];
    }
}
