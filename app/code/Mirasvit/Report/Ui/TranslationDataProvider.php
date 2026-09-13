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
 * @package   mirasvit/module-report
 * @version   1.4.74
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Report\Ui;

use Magento\Backend\Block\Template;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\App\State;
use Magento\Framework\TranslateInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Registry;
use Mirasvit\Core\Service\CspService;

class TranslationDataProvider extends Template
{
    protected $serializer;

    protected $inlineTranslation;

    protected $translator;

    protected $appState;

    protected $localeResolver;

    protected $registry;

    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        StateInterface $inlineTranslation,
        TranslateInterface $translator,
        State $appState,
        ResolverInterface $localeResolver,
        Registry $registry,
        Template\Context $context
    ) {
        $this->serializer = $serializer;
        $this->inlineTranslation = $inlineTranslation;
        $this->translator = $translator;
        $this->appState = $appState;
        $this->localeResolver = $localeResolver;
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * Get translations for React UI from Magento's translation system
     */
    public function getTranslationData()
    {
        $translations = [];

        // Disable inline translation to get clean translated strings
        $this->inlineTranslation->suspend();

        try {
            // Get all translation data from Magento's translation system
            $translationData = $this->translator->getData();

            if (is_array($translationData)) {
                foreach ($translationData as $key => $value) {
                    $slug = $this->keyToSlug($key);
                    $translations[$slug] = $value;
                }
            }
        } catch (\Exception $e) {
            // intentional no-op: translations are optional; React components fall back to their original text
        }

        $this->inlineTranslation->resume();

        return [
            'ui' => $translations,
            'locale' => $this->localeResolver->getLocale()
        ];
    }

    /**
     * Convert translation key to slug for JavaScript usage
     */
    private function keyToSlug($key)
    {
        // Convert spaces to underscores, handle special characters
        $slug = strtolower(str_replace(' ', '_', $key));

        // Handle parameter placeholders - replace all %n patterns with paramn (unlimited parameters)
        $slug = preg_replace('/%(\d+)/', 'param$1', $slug);

        // Remove special characters except underscores and dots
        $slug = preg_replace('/[^a-z0-9_.]/', '', (string)$slug);

        return $slug;
    }

    public function toHtml()
    {
        // Check if Polaris translation data has already been loaded
        if ($this->registry->registry('polaris_translation_loaded')) {
            return ''; // Skip - already loaded by another module
        }

        $translationData = $this->getTranslationData();
        $json = $this->serializer->serialize($translationData);
        $nonce = CspService::getNonce();
        $script = $nonce ? '<script nonce="' . $this->escapeHtml($nonce) . '">' : '<script>';

        // Add debug console log to see what translations are being loaded
        $debugScript = '';
        if ($this->appState->getMode() !== \Magento\Framework\App\State::MODE_PRODUCTION) {
            $debugScript = "console.log('Translation Data:', " . $json . ");";
        }

        // Mark as loaded to prevent duplicate loading from other Polaris modules
        $this->registry->register('polaris_translation_loaded', true);

        return $script . "window.mstTranslationData = $json; $debugScript</script>";
    }
}
