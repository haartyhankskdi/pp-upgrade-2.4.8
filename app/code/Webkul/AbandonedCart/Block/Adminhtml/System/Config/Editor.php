<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\AbandonedCart\Block\Adminhtml\System\Config;

use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Editor extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var  Registry
     */
    protected $_coreRegistry;

    /**
     * @param Context       $context
     * @param WysiwygConfig $wysiwygConfig
     * @param array         $data
     */
    public function __construct(
        Context $context,
        WysiwygConfig $wysiwygConfig,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $data);
    }

    /**
     * get element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return \Magento\Framework\Data\Form\Element\AbstractElement $element
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $identifier = '#webkul_abandoned_cart_abandoned_cart_mail_configuration_abandoned_cart_auto_mail_status';
        // set wysiwyg for element
        $element->setWysiwyg(true);
        // set configuration values
        $element->setConfig($this->_wysiwygConfig->getConfig($element));
        $html = $element->getElementHtml();
        $html .= "<script>
                      require([
                                'jquery'
                            ], function(jQuery){

            function disable(){
              jQuery('#row_".$element->getHtmlId()."').hide();
            }

            function enable(){
              jQuery('#row_".$element->getHtmlId()."').show();
            }
            jQuery(document).ready(function(){
                if(jQuery(".$identifier.").val()==0) {
                    disable();
                }
            })

            jQuery(".$identifier.").change(function(){
                if(jQuery(this).val()==1) {
                    enable();
                } else {
                    disable();
                }
            })
        })
          </script>";
        return $html;
    }
}
