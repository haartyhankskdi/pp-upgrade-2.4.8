<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageplaza.com license that is
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

namespace Mageplaza\GoogleTagManager\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Interface CodeRepositoryInterface
 * @package Mageplaza\GoogleTagManager\Api
 */
interface CodeRepositoryInterface
{
    /**
     * @param string $type
     * @param string $action
     * @param string $id
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return string
     */
    public function getCode($type, $action, $id, SearchCriteriaInterface $searchCriteria = null);

    /**
     * @return string
     */
    public function getGTMCodeHome();

    /**
     * @param string $type
     *
     * @return string
     */
    public function getHead($type);
}
