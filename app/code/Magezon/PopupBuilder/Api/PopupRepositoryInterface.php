<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Api;

interface PopupRepositoryInterface
{
    /**
     * Save popup.
     *
     * @param \Magezon\PopupBuilder\Api\Data\PopupInterface $popup
     * @return \Magezon\PopupBuilder\Api\Data\PopupInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Magezon\PopupBuilder\Api\Data\PopupInterface $popup);

    /**
     * Retrieve popup.
     *
     * @param int $popupId
     * @return \Magezon\PopupBuilder\Api\Data\PopupInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($popupId);

    /**
     * Retrieve popups matching the specified searchCriteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magezon\PopupBuilder\Api\Data\PopupSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete popup.
     *
     * @param \Magezon\PopupBuilder\Api\Data\PopupInterface $popup
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Magezon\PopupBuilder\Api\Data\PopupInterface $popup);

    /**
     * Delete popup by ID.
     *
     * @param int $popupId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($popupId);
}
