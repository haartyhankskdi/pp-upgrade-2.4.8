<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\Cleaning;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Block\Widget\Form\Element\Wysiwyg;
use Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesProcessorInterface;
use Amasty\Customform\Model\Submit;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Widget\Model\Template\Filter as TemplateFilter;

class FieldsCleaner
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var VariablesProcessorInterface
     */
    private $variablesProcessor;

    /**
     * @var State
     */
    private $state;

    /**
     * @var TemplateFilter
     */
    private $templateFilter;

    public function __construct(
        Serializer $serializer,
        VariablesProcessorInterface $variablesProcessor,
        State $state,
        TemplateFilter $templateFilter
    ) {
        $this->serializer = $serializer;
        $this->variablesProcessor = $variablesProcessor;
        $this->state = $state;
        $this->templateFilter = $templateFilter;
    }

    /**
     * @param string $json
     * @return string
     */
    public function cleanJson(string $json): string
    {
        if ($this->state->getAreaCode() != Area::AREA_ADMINHTML) {
            $formConfig = $this->serializer->unserialize($json);

            foreach ($formConfig as &$page) {
                foreach ($page as &$fieldConfig) {
                    if ($fieldConfig['type'] === Wysiwyg::TYPE_NAME) {
                        $fieldConfig[Submit::VALUE] = $this->templateFilter->filter($fieldConfig[Submit::VALUE] ?? '');
                    }

                    $variables = $this->variablesProcessor->extractVariables($fieldConfig[Submit::VALUE] ?? '');

                    if (!empty($variables)) {
                        $fieldConfig[Submit::VALUE] = '';
                    }
                }
            }

            $json = $this->serializer->serialize($formConfig);
        }

        return $json;
    }
}
