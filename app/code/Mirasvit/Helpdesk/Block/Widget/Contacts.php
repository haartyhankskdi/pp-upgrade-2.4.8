<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Block\Widget;

use Mirasvit\Core\Service\CompatibilityService;

class Contacts extends \Mirasvit\Helpdesk\Block\Contact\ContactUsForm implements \Magento\Widget\Block\BlockInterface
{
    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('contacts/form.phtml');
        $this->setData('is_widget', true);
    }

    /**
     * @return \MSP\ReCaptcha\Block\Frontend\ReCaptcha
     */
    public function getRecaptchaBlock()
    {
        if (version_compare(CompatibilityService::getVersion(), "2.4.2", ">=")) {
            $componentName = 'recaptcha';
            $component = 'Magento_ReCaptchaFrontendUi/js/reCaptcha';
            $templateName = 'Magento_ReCaptchaFrontendUi::recaptcha.phtml';
        } else {
            $componentName = 'hdmx-widget-msp-recaptcha';
            $component = 'MSP_ReCaptcha/js/reCaptcha';
            $templateName = 'Mirasvit_Helpdesk::contact/form/msp_recaptcha.phtml';

        }
        /** @var \MSP\ReCaptcha\Block\Frontend\ReCaptcha $block */
        $block = $this->_layout->createBlock(
            'Mirasvit\Helpdesk\Block\MspRecaptcha\Frontend\ReCaptcha\RecaptchaWidget',
            'hdmx-msp-recaptcha-widget',
            [
                'data' => [
                    'jsLayout' => [
                        'components' => [
                            $componentName => [
                                'component'  =>  $component,
                                'zone'       => 'hdmx-widget',
                                'reCaptchaId'=> 'hdmx-widget-msp-recaptcha-id',
                            ],
                        ],
                    ],
                ],
            ]
        );
        $block->setTemplate($templateName);
        $block->setData('scope_id', 'hdmx-widget-msp-recaptcha');

        return $block;
    }
}
