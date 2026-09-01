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

namespace Magezon\PopupBuilder\Model;

class DefaultConfigProvider extends \Magezon\Builder\Model\DefaultConfigProvider
{
    /**
     * @var string
     */
    protected $_builderArea = 'popup';

    /**
     * @return array
     */
    public function getConfig()
    {
        $config = parent::getConfig();
        $config['profile'] = [
            'builder'     => \Magezon\PopupBuilder\Block\Builder::class,
            'home'        => 'https://www.magezon.com/magento-2-popup-builder.html?utm_campaign=mgzbuilder&utm_source=mgz_user&utm_medium=backend',
            'templateUrl' => 'https://www.magezon.com/productfile/popupbuilder/templates.php'
        ];
        return $config;
    }
}
