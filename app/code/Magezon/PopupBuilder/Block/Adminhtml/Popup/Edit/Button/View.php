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

namespace Magezon\PopupBuilder\Block\Adminhtml\Popup\Edit\Button;

class View extends Generic
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $frontendUrlBuilder;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @param \Magento\Framework\View\Element\UiComponent\Context $context
     * @param \Magento\Framework\Registry                         $registry
     * @param \Magento\Framework\AuthorizationInterface           $authorization
     * @param \Magento\Framework\UrlInterface                     $frontendUrlBuilder
     * @param \Magento\Backend\Model\Auth\Session                 $authSession
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Framework\UrlInterface $frontendUrlBuilder,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        parent::__construct($context, $registry, $authorization);
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->authSession        = $authSession;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getCurrentPopup()->getId()) {
            $data = [
                'label'      => __('View'),
                'class'      => 'view',
                'on_click'   => 'window.open(\'' . $this->getViewUrl() . '\', \'_blank\')',
                'sort_order' => 20
            ];
        }
        return $data;
    }

    /**
     * @return string
     */
    public function getViewUrl()
    {
        return $this->frontendUrlBuilder->getUrl(
            'popupbuilder/popup/preview',
            [
                'popup_id' => $this->getCurrentPopup()->getId(),
                'key'      => $this->authSession->getSessionId(),
                '_current' => false,
                '_nosid'   => true
            ]
        );
    }
}
