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

namespace Magezon\PopupBuilder\Block\Adminhtml;

class TopMenu extends \Magezon\Core\Block\Adminhtml\TopMenu
{
    /**
     * Init popup items
     *
     * @return array
     */
    public function intLinks()
    {
        $links = [
            [
                [
                    'title'    => __('Add New Popup'),
                    'link'     => $this->getUrl('popupbuilder/popup/new'),
                    'resource' => 'Magezon_PopupBuilder::popup_save'
                ],
                [
                    'title'    => __('Manage Popup'),
                    'link'     => $this->getUrl('popupbuilder/popup'),
                    'resource' => 'Magezon_PopupBuilder::popup'
                ],
                [
                    'title'    => __('Settings'),
                    'link'     => $this->getUrl('adminhtml/system_config/edit/section/popupbuilder'),
                    'resource' => 'Magezon_PopupBuilder::settings'
                ],
                [
                    'title'    => __('Reports'),
                    'link'     => $this->getUrl('popupbuilder/reports'),
                    'resource' => 'Magezon_PopupBuilder::report'
                ]
            ],
            [
                'class' => 'separator'
            ],
            [
                'title'  => __('User Guide'),
                'link'   => 'https://magezon.com/pub/media/productfile/popupbuilder-user_guides.pdf',
                'target' => '_blank'
            ],
            [
                'title'  => __('Change Log'),
                'link'   => 'https://www.magezon.com/magento-2-popup.html#release_notes',
                'target' => '_blank'
            ],
            [
                'title'  => __('Get Support'),
                'link'   => $this->getSupportLink(),
                'target' => '_blank'
            ]
        ];
        return $links;
    }
}
