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

namespace AutifyDigital\LloydscardnetPayment\Model\Config\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\OptionSourceInterface;
use Psr\Log\LoggerInterface;

/**
 * Card brand options for the transaction grid filter.
 *
 * Built from the brands recorded in the payments table rather than a fixed list:
 * each integration reports the brand in its own vocabulary (PaymentJS
 * "american-express", Google/Apple Pay "AMEX"), so a hardcoded list would not
 * match every stored row.
 */
class Brand implements OptionSourceInterface
{
    /**
     * Payments table holding the recorded brand.
     */
    private const PAYMENTS_TABLE = 'autify_lloydscardnetpayment_payments';

    /**
     * Display names for the brand values the gateways report.
     */
    private const LABELS = [
        'amex' => 'American Express',
        'american-express' => 'American Express',
        'diners-club' => 'Diners Club',
        'discover' => 'Discover',
        'electron' => 'Electron',
        'elo' => 'Elo',
        'jcb' => 'JCB',
        'maestro' => 'Maestro',
        'mastercard' => 'Master Card',
        'mir' => 'Mir',
        'unionpay' => 'UnionPay',
        'visa' => 'Visa',
    ];

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['label' => __('Please Select'), 'value' => '']];

        foreach ($this->getLabelledBrands() as $value => $label) {
            $options[] = ['label' => $label, 'value' => $value];
        }

        return $options;
    }

    /**
     * Map each stored brand to a display label, keyed by the value to filter on.
     *
     * Where two vocabularies share a label the raw value is appended so the
     * entries can be told apart.
     *
     * @return array<string, string>
     */
    private function getLabelledBrands(): array
    {
        $brands = $this->getStoredBrands();

        $labelCounts = [];
        foreach ($brands as $brand) {
            $label = $this->getLabel($brand);
            $labelCounts[$label] = ($labelCounts[$label] ?? 0) + 1;
        }

        $options = [];
        foreach ($brands as $brand) {
            $label = $this->getLabel($brand);
            $options[$brand] = $labelCounts[$label] > 1
                ? sprintf('%s (%s)', $label, $brand)
                : $label;
        }

        asort($options);

        return $options;
    }

    /**
     * Distinct brands present in the payments table, lower-cased.
     *
     * Gateways differ only in case for the same brand ('VISA' vs 'visa'), and the
     * case-insensitive collation means filtering on either matches both.
     *
     * @return string[]
     */
    private function getStoredBrands(): array
    {
        try {
            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->distinct()
                ->from($this->resource->getTableName(self::PAYMENTS_TABLE), ['brand'])
                ->where('brand IS NOT NULL')
                ->where('TRIM(brand) != ?', '');

            $brands = array_map(
                static fn ($brand): string => strtolower(trim((string)$brand)),
                $connection->fetchCol($select)
            );

            return array_values(array_unique(array_filter($brands)));
        } catch (\Exception $e) {
            $this->logger->error('Lloyds: unable to load card brands for grid filter: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Display label for a stored brand value.
     *
     * @param string $brand
     * @return string
     */
    private function getLabel(string $brand): string
    {
        return self::LABELS[$brand] ?? ucwords(str_replace(['-', '_'], ' ', $brand));
    }
}
