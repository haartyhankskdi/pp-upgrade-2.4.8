<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Ui\Component\Form\Buttons;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Answer\FinderInterfaceFactory;
use Amasty\Customform\Model\FormRegistry;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ExportFormDataToPdf implements ButtonProviderInterface
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var FormRegistry
     */
    private $formRegistry;

    /**
     * @var FinderInterfaceFactory
     */
    private $finderFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FormRegistry $formRegistry,
        FinderInterfaceFactory $finderFactory,
        UrlInterface $urlBuilder
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->formRegistry = $formRegistry;
        $this->finderFactory = $finderFactory;
        $this->urlBuilder = $urlBuilder;
    }

    public function getButtonData(): array
    {
        if ($this->getCurrentForm()) {
            $result = [
                'label' => __('Export Submissions to PDF (%1)', $this->getAnswersCount()),
                'class' => '-amasty-customform-export-button',
                'on_click' => sprintf('setLocation(\'%s\')', $this->getExportUrl()),
                'visible' => false
            ];
        }

        return $result ?? [];
    }

    private function getExportUrl(): string
    {
        return $this->urlBuilder->getUrl(
            'amasty_customform/answer/exportFormDataToPdfs',
            [
                'form_id' => $this->getCurrentForm()->getFormId()
            ]
        );
    }

    private function getCurrentForm()
    {
        return $this->formRegistry->getCurrentForm()
            ?: $this->formRegistry->registry(FormRegistry::PERSISTED_DATA);
    }

    private function getAnswersCount(): int
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder = $searchCriteriaBuilder->addFilter(
            AnswerInterface::FORM_ID,
            $this->getCurrentForm()->getFormId()
        );
        $finder = $this->finderFactory->create();
        $finder->setSearchCriteria($searchCriteriaBuilder->create());

        return $finder->getResultsCount();
    }
}
