<?php
/**
 * Copyright © Nilesh All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\Theme\Rewrite\Magento\Framework\View\Element\Html\Link;

class Current extends \Magento\Framework\View\Element\Html\Link\Current
{
     /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        }

        $highlight = '';

        if ($this->getIsHighlighted()) {
            $highlight = ' current';
        }

        $generated_class = str_replace("/","-",$this->getPath());

        if ($this->isCurrent()) {
            $html = '<li class="nav item '.$generated_class.' current">';
            $html .= '<strong>'
                . $this->escapeHtml(__($this->getLabel()))
                . '</strong>';
            $html .= '</li>';
        } else {
            $html = '<li class="nav item '.$generated_class.' ' . $highlight . '"><a href="' . $this->escapeHtml($this->getHref()) . '"';
            $html .= $this->getTitle()
                ? ' title="' . $this->escapeHtml(__($this->getTitle())) . '"'
                : '';
            $html .= $this->getAttributesHtml() . '>';

            if ($this->getIsHighlighted()) {
                $html .= '<strong>';
            }

            $html .= $this->escapeHtml(__($this->getLabel()));

            if ($this->getIsHighlighted()) {
                $html .= '</strong>';
            }

            $html .= '</a></li>';
        }

        return $html;
    }
}

