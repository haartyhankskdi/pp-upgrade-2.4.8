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

namespace Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit;

use Exception;

/**
 * Class Tabs
 *
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{

    protected function _construct()
    {
        parent::_construct();
        $this->setId('mppromobanner_banner_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Promo Banner Information'));
    }

    /**
     * @return \Magento\Backend\Block\Widget\Tabs
     * @throws Exception
     */
    protected function _beforeToHtml()
    {
        $this->addTab('general', [
            'label'   => __('General'),
            'title'   => __('General'),
            'content' => $this->getChildHtml('general'),
            'active'  => true
        ]);

        $this->addTab('condition', [
            'label'   => __('Conditions'),
            'title'   => __('Conditions'),
            'content' => $this->getChildHtml('condition')
        ]);

        $this->addTab('design', [
            'label'   => __('Design'),
            'title'   => __('Design'),
            'content' => $this->getChildHtml('design')
        ]);

        $this->addTab('display', [
            'label'   => __('Display'),
            'title'   => __('Display'),
            'content' => $this->getChildHtml('display')
        ]);

        $this->addTab('trigger', [
            'label'   => __('Trigger'),
            'title'   => __('Trigger'),
            'content' => $this->getChildHtml('trigger')
        ]);

        return parent::_beforeToHtml();
    }
}
