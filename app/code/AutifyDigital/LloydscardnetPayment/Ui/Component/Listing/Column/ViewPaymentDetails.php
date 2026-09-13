<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-2025 Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Locale\CurrencyInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Escaper;
use Magento\Sales\Api\OrderRepositoryInterface;

class ViewPaymentDetails extends Column
{
    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var FormKey
     */
    protected $_formKey;

    /**
     * @var CurrencyInterface
     */
    private $currency;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Escaper
     */
    private $escaper;
    
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var array Payment status mapping
     */
    private $paymentStatusArray = [
        '1' => 'Pending',
        '2' => 'Paid',
        '3' => 'Cancelled',
        '4' => 'Error',
        '5' => 'Refunded',
    ];

    /**
     * Construct
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $_urlBuilder
     * @param FormKey $_formKey
     * @param CurrencyInterface $currency
     * @param Data $helper
     * @param Escaper $escaper
     * @param OrderRepositoryInterface $orderRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $_urlBuilder,
        FormKey $_formKey,
        CurrencyInterface $currency,
        Data $helper,
        Escaper $escaper,
        OrderRepositoryInterface $orderRepository,
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $_urlBuilder;
        $this->_formKey = $_formKey;
        $this->currency = $currency;
        $this->helper = $helper;
        $this->escaper = $escaper;
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                $item[$name . '_html'] = $this->generatePaymentDetailsHtml($item);

                $item[$name] = '<a class="action-view-payment-details">' . __('View Details') . '</a>';
            }
        }
        return $dataSource;
    }

    /**
     * Generate payment details HTML for popup
     *
     * @param array $item
     * @return string
     */
    private function generatePaymentDetailsHtml($item)
    {
        $html = '<div class="lloyd-cardnet-payment-container">';
        
        $html .= '<div class="lloyd-cardnet-section">';
        $html .= '<h3 class="lloyd-cardnet-section-title">' . __('Payment Information') . '</h3>';
        $html .= '<table class="admin__table-secondary lloyd-cardnet-info-table"><tbody>';
        
        if (!empty($item['ipgTransactionId'])) {
            $html .= '<tr><th>' . __('Payment ID') . '</th><td><span class="payment_id">' .
            $this->escaper->escapeHtml($item['ipgTransactionId']) . '</span></td></tr>';
        }
        
        if (!empty($item['status'])) {
            $status = $item['status'];
            $statusClass = $this->helper->getStatusClass($status);
            $statusText = $this->paymentStatusArray[$status] ?? $status;
            $html .= '<tr><th>' . __('Status') . '</th><td><span class="' .
            $statusClass . '">' .
            $this->escaper->escapeHtml($statusText) . '</span></td></tr>';
        }
        
        if (!empty($item['amount'])) {
            $currencySymbol = $this->getCurrencySymbol($item);
            $html .= '<tr><th>' . __('Amount') . '</th><td><span class="amount">' .
            $this->escaper->escapeHtml($currencySymbol . number_format((float)$item['amount'], 2, '.', '')) .
            '</span></td></tr>';
            $html .= '<tr><th>' . __('Captured') . '</th><td><span class="amount">' .
            $this->escaper->escapeHtml($currencySymbol .number_format((float)$item['amount'], 2, '.', '')) .
            '</span></td></tr>';
        }
       
        $html .= '<tr><th>' . __('Refunded') . '</th><td><span class="amount">' .
                $this->escaper->escapeHtml(
                    $this->getCurrencySymbol($item) .
                    number_format(
                        !empty($this->helper->getRefundAmount($item['order_id'])) ?
                        $this->helper->getRefundAmount($item['order_id']) : 0.00,
                        2,
                        '.',
                        ''
                    )
                ) .
            '</span></td></tr>';
        
        if (!empty($item['payment_method'])) {
            $html .= '<tr><th>' . __('Payment Method') . '</th><td><span class="payment-method-badge">' .
            $this->escaper->escapeHtml($this->helper->getPaymentMethod($item['payment_method'])) . '</span></td></tr>';
        }
        
        if (!empty($item['created_at'])) {
            $html .= '<tr><th>' . __('Payment Date') . '</th><td>' .
            $this->escaper->escapeHtml($item['created_at']) . '</td></tr>';
        }
        
        if (!empty($item['remote_reference'])) {
            $html .= '<tr><th>' . __('Gateway Reference') . '</th><td>' .
            $this->escaper->escapeHtml($item['remote_reference']) . '</td></tr>';
        }
        

        if (!empty($item['response_3ds_code_message'])) {
            $responseMessage = $this->helper->getResponseMessage($item['response_3ds_code_message']);
            if(!empty($responseMessage)) {
                $html .= '<tr><th>' . __('Gateway Message') .
                '</th><td><div class="three-ds-message">' . $this->escaper->escapeHtml((string)$responseMessage) .
                '</div></td></tr>';
            }
        }
        
        $html .= '</tbody></table></div>';
        
        $html .= '<div class="lloyd-cardnet-section">';
        $html .= '<h3 class="lloyd-cardnet-section-title">' . __('Fraud Details') . '</h3>';
        $html .= '<table class="admin__table-secondary lloyd-cardnet-info-table"><tbody>';
        
        if (!empty($item['street_match_original'])) {
            $html .= '<tr><th>' . __('Street Check') . '</th><td>' .
            $this->helper->getCheckResult($item['street_match_original']) . '</td></tr>';
        } else {
            $html .= '<tr><th>' . __('Street Check') . '</th><td>
            <span class="check-unchecked">' . __('Unchecked') . '</span></td></tr>';
        }
        
        if (!empty($item['postcode_match_original'])) {
            $html .= '<tr><th>' . __('Postcode Check') . '</th><td>' .
            $this->helper->getCheckResult($item['postcode_match_original']) . '</td></tr>';
        } else {
            $html .= '<tr><th>' . __('Postcode Check') . '</th><td>
            <span class="check-unchecked">' . __('Unchecked') . '</span></td></tr>';
        }
        
        if (!empty($item['cvv_match_original'])) {
            $html .= '<tr><th>' . __('CVV Check') . '</th><td>' .
            $this->helper->getCheckResult($item['cvv_match_original']) . '</td></tr>';
        } else {
            $html .= '<tr><th>' . __('CVV Check') . '</th><td>
            <span class="check-unchecked">' . __('Unchecked') . '</span></td></tr>';
        }
        
        $html .= '</tbody></table></div>';
        
        $html .= '<div class="lloyd-cardnet-section">';
        $html .= '<h3 class="lloyd-cardnet-section-title">' . __('Card Details') . '</h3>';
        $html .= '<table class="admin__table-secondary lloyd-cardnet-info-table"><tbody>';
        
        if (!empty($item['brand'])) {
            $html .= '<tr><th>' . __('Brand') . '</th><td><div class="card-brand"><img class="card-icon ' .
            strtolower($item['brand']) . '" src="' .
            $this->escaper->escapeUrl($this->helper->getPaymentMethodIcon($item['brand'])) .'"></div></td></tr>';
        }
        
        if (!empty($item['last4'])) {
            $html .= '<tr><th>' . __('Last 4') . '</th><td><span class="masked-value">' .
            $this->escaper->escapeHtml($item['last4']) . '</span></td></tr>';
        }
        
        if (!empty($item['country'])) {
            $html .= '<tr><th>' . __('Country') . '</th><td>' .
            $this->escaper->escapeHtml($item['country']) . '</td></tr>';
        }
        
        if (!empty($item['exp_month'])) {
            $html .= '<tr><th>' . __('Expiration') . '</th><td><span class="masked-value">'.
            $this->escaper->escapeHtml($item['exp_month']) . ' / ' .
            $this->escaper->escapeHtml($item['exp_year']) .'</span></td></tr>';
        }
        
        if (!empty($item['cardnet_refund_id'])) {
            $html .= '<tr><th>' . __('Cardnet Refund ID') . '</th><td>' .
            $this->escaper->escapeHtml($item['cardnet_refund_id']) . '</td></tr>';
        }
        
        if (!empty($item['remote_status_or_code'])) {
            $remoteStatusClass = $this->helper->getRemoteStatusClass($item['remote_status_or_code']);
            $html .= '<tr><th>' . __('Gateway Status') . '</th><td><span class="' .
            $remoteStatusClass . '">' . $this->escaper->escapeHtml($item['remote_status_or_code']) .
            '</span></td></tr>';
        }
        
        $html .= '</tbody></table></div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get currency symbol
     *
     * @param array $item
     * @return string
     */
    private function getCurrencySymbol($item)
    {
        $currencyCode = 'GBP';
        if (!empty($item['order_id'])) {
            try {
                $order = $this->orderRepository->get($item['order_id']);
                $currencyCode = $order->getOrderCurrencyCode();
            } catch (\Exception $e) {
                return $currencyCode;
            }
        }
        
        try {
            return $this->currency->getCurrency($currencyCode)->getSymbol();
        } catch (\Exception $e) {
            return '£';
        }
    }
}
