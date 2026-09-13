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



namespace Mirasvit\Helpdesk\Service\Config;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\UiConfigResolverInterface;
use Magento\Framework\View\Element\Template\Context;


if (class_exists('Magento\ReCaptchaUi\Block\ReCaptcha')) {

    class RecaptchaConfig extends \Magento\ReCaptchaUi\Block\ReCaptcha
    {
        private $captchaUiConfigResolver;

        public function __construct(
            Context                   $context,
            UiConfigResolverInterface $captchaUiConfigResolver,
            IsCaptchaEnabledInterface $isCaptchaEnabled,
            Json                      $serializer,
            array                     $data = []
        ) {

            $this->captchaUiConfigResolver = $captchaUiConfigResolver;
            $this->isCaptchaEnabled        = $isCaptchaEnabled;
            $this->serializer              = $serializer;
            parent::__construct($context, $captchaUiConfigResolver, $isCaptchaEnabled, $serializer, $data);
        }

        /**
         * {@inheritdoc}
         */
        public function getCaptchaUiConfig(): array
        {
            $key = 'contact';

            if ($this->isCaptchaEnabled->isCaptchaEnabledFor($key)) {
                $uiConfig = $this->getData('captcha_ui_config');

                if ($uiConfig) {
                    $uiConfig = array_replace_recursive($this->captchaUiConfigResolver->get($key), $uiConfig);
                } else {
                    $uiConfig = $this->captchaUiConfigResolver->get($key);
                }

                return $uiConfig;
            }

            return [];
        }
    }

} else {
    class RecaptchaConfig
    {
    }
}
