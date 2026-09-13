<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders the AVS / Postcode / CVV check result as a traffic-light badge on the
 * order grid: green tick for a pass (Y), red cross for a fail (N) and an amber
 * badge for anything else (unchecked / not authenticated).
 *
 * The same class backs all three columns; the field it renders is taken from the
 * column "name" so it stays in sync with the joined grid collection fields.
 */
class AvsCheckColumn extends Column
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $item[$fieldName] = $this->renderBadge($item[$fieldName] ?? null);
        }

        return $dataSource;
    }

    /**
     * Build the traffic-light badge HTML for a single check value.
     *
     * @param string|null $value
     * @return string
     */
    private function renderBadge(?string $value): string
    {
        switch (strtoupper((string) $value)) {
            case 'Y':
                return $this->badge('#1e7e34', '#e6f4ea', '&#10004;', (string) __('Pass'));
            case 'N':
                return $this->badge('#b21f2d', '#fbe7e9', '&#10006;', (string) __('Fail'));
            default:
                return $this->badge('#9a6a00', '#fff4d6', '&#9888;', (string) __('Not Authenticated'));
        }
    }

    /**
     * Build a coloured pill containing an icon and a label.
     *
     * @param string $color
     * @param string $background
     * @param string $icon
     * @param string $label
     * @return string
     */
    private function badge(string $color, string $background, string $icon, string $label): string
    {
        return sprintf(
            '<span style="display:inline-flex;align-items:center;gap:5px;padding:2px 9px;'
            . 'border-radius:11px;background:%s;color:%s;font-weight:600;line-height:18px;white-space:nowrap;">'
            . '<span style="font-size:12px;line-height:1;">%s</span>%s</span>',
            $background,
            $color,
            $icon,
            $this->escaper->escapeHtml($label)
        );
    }
}
