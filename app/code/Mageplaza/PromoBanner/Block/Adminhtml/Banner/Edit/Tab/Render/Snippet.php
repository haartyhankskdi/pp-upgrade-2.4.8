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

namespace Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Snippet
 *
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render
 */
class Snippet extends AbstractElement
{
    /**
     * Get element html
     *
     * @return string
     */
    public function getElementHtml()
    {
        $subject = $this->getData('subject');

        $currentBanner = $subject->getCurrentBanner();
        $bannerId      = $currentBanner->getId() ?: '';

        $html = '<div id="' . $this->getHtmlId() . '"' . $this->serialize(['class']) . $this->_getUiId() . '>' . "\n";

        $html .= '<div class="control-value" style="padding-top: 8px;">';
        $html .= '<span><p>' . __('Use the following code to show the promo banner block in any place you want') . '</p></span>';

        $html .= '<strong>' . __('CMS Page/Static Block') . '</strong><br />';
        $html .= '<span style="font-size: 10px"><pre style="background-color: #f5f5dc"><code>{{block class="Mageplaza\PromoBanner\Block\Banner\SnippetCode" banner_id="' . $bannerId . '"}}</code></pre></span>';

        $html .= '<strong>' . __('Banner .phtml file') . '</strong><br />';
        $html .= '<span style="font-size: 10px"><pre style="background-color: #f5f5dc"><code>' . $this->_escaper->escapeHtml(
            '<?php echo $block->getLayout()->createBlock("Mageplaza\PromoBanner\Block\Banner\SnippetCode")
        ->setBannerId("' . $bannerId . '")->toHtml();?>'
        ) . '</code></pre></span>';

        $html .= '<strong>' . __('Layout file') . '</strong><br />';
        $html .= '<span style="font-size: 10px"><pre style="background-color: #f5f5dc"><code>' . $this->_escaper->escapeHtml('<block class="Mageplaza\PromoBanner\Block\Banner\SnippetCode" name="mppromobanner_snippet">
    <arguments>
        <argument name="banner_id" xsi:type="string">' . $bannerId . '</argument>
    </arguments>
</block>') . '</code></pre></span>';

        $html .= '</div>';
        $html .= '</div>' . "\n";

        return $html;
    }
}
