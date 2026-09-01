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

namespace Magezon\PopupBuilder\Block\Adminhtml\Dashboard;

class Report extends \Magento\Backend\Block\Template
{
    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var array
     */
    protected $layoutProcessors;

    /**
     * @var \Magezon\PopupBuilder\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magezon\PopupBuilder\Model\Source\PopupList
     */
    protected $popupList;

    /**
     * @param \Magento\Backend\Block\Template\Context      $context
     * @param \Magezon\PopupBuilder\Helper\Data            $dataHelper
     * @param \Magezon\PopupBuilder\Model\Source\PopupList $popupList
     * @param array                                        $layoutProcessors
     * @param array                                        $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magezon\PopupBuilder\Helper\Data $dataHelper,
        \Magezon\PopupBuilder\Model\Source\PopupList $popupList,
        array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context);
        $this->jsLayout         = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->layoutProcessors = $layoutProcessors;
        $this->dataHelper       = $dataHelper;
        $this->popupList        = $popupList;
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }
        return json_encode($this->jsLayout, JSON_HEX_TAG);
    }

    /**
     * @return bool|string
     * @since 100.2.0
     */
    public function getConfig()
    {
        return json_encode($this->getInventoryConfig(), JSON_HEX_TAG);
    }

    /**
     * Retrieve inventory configuration
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getInventoryConfig()
    {
        $popups = $this->popupList->toOptionArray();
        $stores = $this->dataHelper->getStores();
        $stores[0]['value'] = 'all';
        $config = [
            'stores' => $stores,
            'items'  => $popups
        ];

        $params = $this->getRequest()->getParams();
        if (isset($params['item_id']) && $params['item_id']) {
            $config['item_id'] = $params['item_id'];
        } else {
            if (!empty($popups)) {
                $config['item_id'] = $popups[0]['value'];
            }
        }

        if (isset($params['from']) && $params['from']) {
            $config['from'] = $params['from'];
        }

        if (isset($params['to']) && $params['to']) {
            $config['to'] = $params['to'];
        }

        if (isset($params['store_id']) && $params['store_id']) {
            $config['store_id'] = $params['store_id'];
        }

        $config['update_url'] = $this->getUrl('popupbuilder/reports/loadReports');
        return $config;
    }
}
