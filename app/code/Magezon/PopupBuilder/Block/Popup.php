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

namespace Magezon\PopupBuilder\Block;

class Popup extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'Magezon_PopupBuilder::popup.phtml';

    /**
     * @var \Magezon\Builder\Helper\Data
     */
    protected $builderHelper;

    /**
     * @var \Magezon\Core\Helper\Style
     */
    protected $styleHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magezon\Builder\Helper\Data                     $builderHelper
     * @param \Magezon\Core\Helper\Style                       $styleHelper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magezon\Builder\Helper\Data $builderHelper,
        \Magezon\Core\Helper\Style $styleHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->builderHelper = $builderHelper;
        $this->styleHelper   = $styleHelper;
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        $popup = $this->getPopup();
        if (!$popup) {
            return;
        }
        return parent::toHtml();
    }

    /**
     * @return string
     */
    public function getProfileHtml()
    {
        $popup = $this->getPopup();
        $block = $this->builderHelper->prepareProfileBlock(\Magezon\Builder\Block\Profile::class, $popup->getContent());
        $html = str_replace(['[POPUP_CLOSE_TIME]'], '<span class="popupbuilder-timer"></span>', $block->toHtml() ?: '');
        preg_match_all('/<form[\s\r\n]+.*?>/is', $html, $matches, PREG_SET_ORDER);
        if ($matches) {
            $search = $replace = [];
            foreach ($matches[0] as $match) {
                $search[]  = $match;
                $replace[] = $match . '<input type="hidden" name="popup_id" value="' . $popup->getId() . '"/>';
            }
            $html = str_replace($search, $replace, $html);
        }
        return $html;
    }

    /**
     * @return array
     */
    public function getJsSettings()
    {
        $popup                   = $this->getPopup();
        $settings                = [];
        $settings['id']          = $popup->getId();
        $settings['display']     = $popup->getDisplaySettings();
        $settings['trigger']     = $popup->getConditions();
        $settings['style']       = $popup->getStyleSettings();
        $settings['htmlId']      = $popup->getHtmlId();
        $settings['customClass'] = $popup->getStyleSettings('popup/custom_class');
        $settings['reportUrl']   = $this->getUrl('popupbuilder/popup/report');
        $settings['previewMode'] = $this->getPreviewMode() ? true : false;
        $settings['isValid']     = $popup->isValid();
        unset($settings['conditions']);
        return $settings;
    }

    /**
     * @return string
     */
    public function getPopupStyleHtml()
    {
        $styleHelper = $this->styleHelper;
        $popup = $this->getPopup();
        $height = $popup->getDisplaySettings('height');
        $styles = $containerStyles = [];
        if ($height == 'custom') {
            $styles['height'] = $styleHelper->getProperty($popup->getDisplaySettings('custom_height'));
        }
        if ($height == 'fit_to_screen') {
            $styles['height'] = '100vh';
        }

        $contentPosition = $popup->getDisplaySettings('content_position');
        switch ($contentPosition) {
            case 'center':
                $styles['align-items'] = 'center';
                break;

            case 'bottom':
                $styles['align-items'] = 'flex-end';
                break;
        }

        $stylingStyles = $this->styleHelper->getStyles($popup->getStyleSettings('popup'));
        $styles = array_merge($styles, $stylingStyles);
        if ($popup->getDisplaySettings('entrance_animation')) {
            if ($entranceAnimationDuration = $popup->getDisplaySettings('animation_duration')) {
                $styles['animation-duration'] = $entranceAnimationDuration . 's';
            }
        }
        $styles['width'] = $styleHelper->getProperty($popup->getDisplaySettings('width'));
        $html = $styleHelper->getHtml('#' . $popup->getHtmlId() . ' .popupbuilder-widget-content', $styles);
        return $html;
    }

    /**
     * @return string
     */
    public function getOverlayStyleHtml()
    {
        $popup = $this->getPopup();
        if ($popup->getDisplaySettings('overlay')) {
            $styles = $this->styleHelper->getStyles($popup->getStyleSettings('overlay'));
            if (!$styles['background-color']) {
                $styles['background-color'] = 'rgba(0,0,0,.8)';
            }
            $styles['pointer-events'] = 'all';
            return $this->styleHelper->getHtml('#' . $popup->getHtmlId(), $styles);
        }
    }

    /**
     * @return string
     */
    public function getCloseButtonStyleHtml()
    {
        $popup = $this->getPopup();
        $styleHtml = '';
        $styles = [];
        $styles['right'] = $this->styleHelper->getProperty($popup->getStyleSettings('closebutton/horizontal_position'));
        $styles['top'] = $this->styleHelper->getProperty($popup->getStyleSettings('closebutton/vertical_position'));
        $styles['width'] = $this->styleHelper->getProperty($popup->getStyleSettings('closebutton/box_size'));
        $styles['height'] = $this->styleHelper->getProperty($popup->getStyleSettings('closebutton/box_size'));
        $styles['border-radius'] = $this->styleHelper->getProperty(
            $popup->getStyleSettings('closebutton/border_radius')
        );
        $styles['color'] = $this->styleHelper->getColor($popup->getStyleSettings('closebutton/color'));
        $styles['background-color'] = $this->styleHelper->getColor(
            $popup->getStyleSettings('closebutton/background_color')
        );
        if ($popup->getStyleSettings('closebutton/boxshadow')) {
            $stylingStyles = $this->styleHelper->getStyles($popup->getStyleSettings('closebutton'));
            $styles = array_merge($styles, $stylingStyles);
        }
        $styleHtml .= $this->styleHelper->getHtml('#' . $popup->getHtmlId() . ' .popupbuilder-popup-close', $styles);
        $styles = [];
        $styles['color'] = $this->styleHelper->getColor($popup->getStyleSettings('closebutton/hover_color'));
        $styles['background-color'] = $this->styleHelper->getColor(
            $popup->getStyleSettings('closebutton/hover_background_color')
        );
        $styleHtml .= $this->styleHelper->getHtml(
            '#' . $popup->getHtmlId() . ' .popupbuilder-popup-close',
            $styles,
            ':hover'
        );

        $styles = [];
        $styles['font-size'] = $this->styleHelper->getProperty($popup->getStyleSettings('closebutton/icon_size'));
        $styleHtml .= $this->styleHelper->getHtml(
            '#' . $popup->getHtmlId() . ' .popupbuilder-popup-close .mgz-icon',
            $styles
        );

        return $styleHtml;
    }

    /**
     * @return string
     */
    public function getStyleHtml()
    {
        $popup = $this->getPopup();
        $styleHtml = '';
        $styleHtml .= $this->getPopupStyleHtml();
        $styleHtml .= $this->getOverlayStyleHtml();
        $styleHtml .= $this->getCloseButtonStyleHtml();
        $styleHtml .= $popup->getStyleSettings('popup/custom_css');
        return $styleHtml;
    }
}
