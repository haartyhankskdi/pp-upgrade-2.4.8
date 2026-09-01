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



namespace Mirasvit\Helpdesk\Block\MspRecaptcha\Frontend\ReCaptcha;

use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\Template;

class Recaptcha extends Template
{
    protected $isPopup     = false;

    protected $jsScopeName = 'msp-recaptcha';

    private $data;

    private $decoder;

    private $encoder;

    private $context;

    public function __construct(
        Template\Context $context,
        DecoderInterface $decoder,
        EncoderInterface $encoder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->context = $context;
        $this->data    = $data;
        $this->decoder = $decoder;
        $this->encoder = $encoder;
    }

    /**
     * @inheritDoc
     */
    public function toHtml()
    {
        if ($this->isPopup && $recaptchaBlock = $this->getChildBlock('hdmx-popup-recaptcha')) {
            return $recaptchaBlock->toHtml();
        }

        if (!class_exists('MSP\ReCaptcha\Model\LayoutSettings', false)) {
            return '';
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \MSP\ReCaptcha\Model\LayoutSettings $layoutSettings */
        $layoutSettings = $objectManager->get('MSP\ReCaptcha\Model\LayoutSettings');
        /** @var \MSP\ReCaptcha\Block\Frontend\ReCaptcha $captchaBlock */
        $captchaBlock = $this->getLayout()->createBlock('MSP\ReCaptcha\Block\Frontend\ReCaptcha',
            $this->jsScopeName,
            [
                'context'        => $this->context,
                'decoder'        => $this->decoder,
                'encoder'        => $this->encoder,
                'layoutSettings' => $layoutSettings,
                'data'           => [
                    'jsLayout' => $this->getJsLayoutData(),
                ],
            ]);

        if ($captchaBlock) {
            if ($this->isPopup) {
                $captchaBlock->setData('scope_id', $this->jsScopeName);
            }

            $captchaBlock->setTemplate($this->getTemplate());

            return $captchaBlock->toHtml();
        } else {
            return '';
        }
    }

    /**
     * @return array
     */
    private function getJsLayoutData()
    {
        $data = $this->jsLayout;

        if ($this->isPopup && class_exists('MSP\ReCaptcha\Model\LayoutSettings', false)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \MSP\ReCaptcha\Model\LayoutSettings $layoutSettings */
            $layoutSettings                                     = $objectManager->get('MSP\ReCaptcha\Model\LayoutSettings');
            $data['components'][$this->jsScopeName]['settings'] = $layoutSettings->getCaptchaSettings();

            $data['components'][$this->jsScopeName]['settings']['enabled']['hdmx-widget'] = true;
            $data['components'][$this->jsScopeName]['settings']['enabled']['hdmx-popup']  = true;

            $data['components'][$this->jsScopeName]['nameInLayout'] = $this->getNameInLayout();
        }

        return $data;
    }
}
