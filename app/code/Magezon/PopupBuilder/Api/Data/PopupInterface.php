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

namespace Magezon\PopupBuilder\Api\Data;

interface PopupInterface
{
	const POPUP_ID         = 'popup_id';
	const NAME             = 'name';
	const CONTENT          = 'content';
	const CONDITIONS       = 'conditions';
	const DISPLAY_SETTINGS = 'display_settings';
	const STYLE_SETTINGS   = 'style_settings';
	const FROM_DATE        = 'from_date';
	const TO_DATE          = 'to_date';
	const IS_ACTIVE        = 'is_active';
	const CREATION_TIME    = 'creation_time';
	const UPDATE_TIME      = 'update_time';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     *
     * @param int $id
     * @return PopupInterface
     */
    public function setId($id);

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     *
     * @param string $name
     * @return PopupInterface
     */
    public function setName($name);

    /**
     * Get content
     *
     * @return string|null
     */
    public function getContent();

    /**
     * Set content
     *
     * @param string $content
     * @return PopupInterface
     */
    public function setContent($content);

    /**
     * Get conditions
     *
     * @param string|null $key
     * @return string|null
     */
    public function getConditions($key = '');

    /**
     * Set conditions
     *
     * @param string $conditions
     * @return PopupInterface
     */
    public function setConditions($conditions);

    /**
     * Get display settings
     *
     * @param string|null $key
     * @return string|null
     */
    public function getDisplaySettings($key = '');

    /**
     * Set display settings
     *
     * @param string $displaySettings
     * @return PopupInterface
     */
    public function setDisplaySettings($displaySettings);

    /**
     * Get style settings
     *
     * @param string|null $key
     * @return string|null
     */
    public function getStyleSettings($key = '');

    /**
     * Set style settings
     *
     * @param string $styleSettings
     * @return PopupInterface
     */
    public function setStyleSettings($styleSettings);

    /**
     * Get from date
     *
     * @return string|null
     */
    public function getFromDate();

    /**
     * Set from date
     *
     * @param string $fromDate
     * @return PopupInterface
     */
    public function setFromDate($fromDate);

    /**
     * Get to date
     *
     * @return string|null
     */
    public function getToDate();

    /**
     * Set to date
     *
     * @param string $toDate
     * @return PopupInterface
     */
    public function setToDate($toDate);

    /**
     * Is active
     *
     * @return bool|null
     */
    public function isActive();

    /**
     * Set is active
     *
     * @param int|bool $isActive
     * @return PopupInterface
     */
    public function setIsActive($isActive);

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreationTime();

    /**
     * Set creation time
     *
     * @param string $creationTime
     * @return PopupInterface
     */
    public function setCreationTime($creationTime);

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdateTime();

    /**
     * Set update time
     *
     * @param string $updateTime
     * @return PopupInterface
     */
    public function setUpdateTime($updateTime);
}