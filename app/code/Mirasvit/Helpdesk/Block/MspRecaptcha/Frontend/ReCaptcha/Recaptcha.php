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



namespace Mirasvit\Helpdesk\Block\MspRecaptcha\Frontend\ReCaptcha;

use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\Template;
use Mirasvit\Core\Service\CompatibilityService;
use Mirasvit\Helpdesk\Service\Config\RecaptchaConfig;

class Recaptcha extends Template
{
    protected $isPopup     = false;

    protected $jsScopeName = 'msp-recaptcha';

    private $data;

    private $recaptchaConfig;

    private $decoder;

    private $encoder;

    private $context;

    public function __construct(
        Template\Context $context,
        RecaptchaConfig  $recaptchaConfig,
        DecoderInterface $decoder,
        EncoderInterface $encoder,
        array            $data = []
    ) {
        parent::__construct($context, $data);
        $this->context         = $context;
        $this->data            = $data;
        $this->recaptchaConfig = $recaptchaConfig;
        $this->decoder         = $decoder;
        $this->encoder         = $encoder;
    }

    /**
     * @inheritDoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function toHtml()
    {
        if (($this->isPopup || $this->getRequest()->getControllerName() == 'satisfaction')
            && $recaptchaBlock = $this->getChildBlock('hdmx-popup-recaptcha')) {
            return $recaptchaBlock->toHtml();
        }

        if (version_compare(CompatibilityService::getVersion(), "2.4.2", ">=")
            && class_exists('Magento\ReCaptchaUi\Block\ReCaptcha', false)) {
            $layoutSettings = $this->recaptchaConfig->getCaptchaUiConfig();
            $className      = 'Magento\ReCaptchaUi\Block\ReCaptcha';
        } elseif (class_exists('MSP\ReCaptcha\Model\LayoutSettings', false)
            && version_compare(CompatibilityService::getVersion(), "2.4.2", "<")) {
            $objectManager  = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \MSP\ReCaptcha\Model\LayoutSettings $layoutSettings */
            $layoutSettings = $objectManager->get('MSP\ReCaptcha\Model\LayoutSettings');
            $className      = 'MSP\ReCaptcha\Block\Frontend\ReCaptcha';
        } else {
            return '';
        }

        $captchaBlock = $this->getLayout()->createBlock($className,
            $this->jsScopeName,
            [
                'context'        => $this->context,
                'decoder'        => $this->decoder,
                'encoder'        => $this->encoder,
                'layoutSettings' => $layoutSettings,
                'data'           => [
                    'recaptcha_for' =>'contact',
                    'jsLayout'      => $this->getJsLayoutData(),
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
