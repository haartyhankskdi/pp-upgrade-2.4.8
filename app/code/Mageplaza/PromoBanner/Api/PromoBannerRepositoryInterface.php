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
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Api;

/**
 * Interface PromoBannerRepositoryInterface
 * @package Mageplaza\PromoBanner\Api
 */
interface PromoBannerRepositoryInterface
{
    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria The search criteria.
     *
     * @return \Mageplaza\PromoBanner\Api\Data\PromoBannerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null);

    /**
     * @param int $id
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function delete($id);

    /**
     * @param \Mageplaza\PromoBanner\Api\Data\PromoBannerInterface $promoBanner
     *
     * @return \Mageplaza\PromoBanner\Api\Data\PromoBannerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function save($promoBanner);

    /**
     * @param string $actionName
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     *
     * @return \Mageplaza\PromoBanner\Api\Data\PromoBannerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPromoBannersByPage(
        $actionName,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null
    );

    /**
     * @return \Mageplaza\PromoBanner\Api\Data\ConfigInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfig();
}
