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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Block\Widget;

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
        /** @var \MSP\ReCaptcha\Block\Frontend\ReCaptcha $block */
        $block = $this->_layout->createBlock(
            'Mirasvit\Helpdesk\Block\MspRecaptcha\Frontend\ReCaptcha\RecaptchaWidget',
            'hdmx-msp-recaptcha-widget',
            [
                'data' => [
                    'jsLayout' => [
                        'components' => [
                            'hdmx-widget-msp-recaptcha' => [
                                'component'  => 'MSP_ReCaptcha/js/reCaptcha',
                                'zone'       => 'hdmx-widget',
                                'reCaptchaId'=> 'hdmx-widget-msp-recaptcha-id',
                            ],
                        ],
                    ],
                ],
            ]
        );
        $block->setTemplate('Mirasvit_Helpdesk::contact/form/msp_recaptcha.phtml');
        $block->setData('scope_id', 'hdmx-widget-msp-recaptcha');

        return $block;
    }
}