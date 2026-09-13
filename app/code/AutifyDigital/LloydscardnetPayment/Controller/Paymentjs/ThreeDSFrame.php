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

namespace AutifyDigital\LloydscardnetPayment\Controller\Paymentjs;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Encryption\EncryptorInterface;

class ThreeDSFrame implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var ModuleDirReader
     */
    private $moduleDirReader;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var DynamicCollector
     */
    private $dynamicCollector;

    /**
     * @param RequestInterface $request
     * @param RawFactory $resultRawFactory
     * @param RedirectFactory $redirectFactory
     * @param ModuleDirReader $moduleDirReader
     * @param File $fileDriver
     * @param Escaper $escaper
     * @param UrlInterface $urlBuilder
     * @param DynamicCollector $dynamicCollector
     * @param Data $helper
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        RequestInterface $request,
        RawFactory $resultRawFactory,
        RedirectFactory $redirectFactory,
        ModuleDirReader $moduleDirReader,
        File $fileDriver,
        Escaper $escaper,
        UrlInterface $urlBuilder,
        DynamicCollector $dynamicCollector,
        Data $helper,
        EncryptorInterface $encryptor
    ) {
        $this->request = $request;
        $this->resultRawFactory = $resultRawFactory;
        $this->redirectFactory = $redirectFactory;
        $this->moduleDirReader = $moduleDirReader;
        $this->fileDriver = $fileDriver;
        $this->escaper = $escaper;
        $this->urlBuilder = $urlBuilder;
        $this->dynamicCollector = $dynamicCollector;
        $this->helper = $helper;
        $this->encryptor = $encryptor;
    }

    /**
     * Load template for iframe
     *
     * @param string $templatePath
     * @param array $replacements
     * @return string
     * @throws \Exception
     */
    private function loadTemplate($templatePath, $replacements = [])
    {
        $possiblePaths = [
            $this->moduleDirReader->getModuleDir(Dir::MODULE_VIEW_DIR, 'AutifyDigital_LloydscardnetPayment') .
            $templatePath,
            $this->moduleDirReader->getModuleDir('', 'AutifyDigital_LloydscardnetPayment') . '/view' . $templatePath,
            $this->moduleDirReader->getModuleDir('', 'AutifyDigital_LloydscardnetPayment') . $templatePath
        ];

        $content = null;
        foreach ($possiblePaths as $filePath) {
            if ($this->fileDriver->isExists($filePath)) {
                $content = $this->fileDriver->fileGetContents($filePath);
                break;
            }
        }

        if ($content === null) {
            $this->helper->addLog('No template found, using fallback');
            throw new \Exception('3DS template file not found');
        }

        foreach ($replacements as $placeholder => $value) {
            $value = $value ?? '';

            if ($placeholder === '{THREEDS_DATA}') {
                // Sanitize the 3DS HTML content while preserving form functionality
                $sanitizedValue = $this->sanitizeThreeDSHtml($value);
                $content = str_replace($placeholder, $sanitizedValue, $content);
            } else {
                $escapedValue = $this->escaper->escapeHtml($value);
                $content = str_replace($placeholder, $escapedValue, $content);
            }
        }

        return $content;
    }

    /**
     * Execute for loading iframe to resolve CSP issue
     */
    public function execute()
    {
        $this->helper->addLog('ThreeDSFrame started');

        $threeDSData = $this->encryptor->decrypt($this->request->getParam('threeds_data'));
        $ipgId = $this->encryptor->decrypt($this->request->getParam('ipg_id'));
        $orderId = $this->request->getParam('order_id');

        try {
            if (!$threeDSData || !$ipgId) {
                $this->helper->addLog('Missing required parameters - threeDSData: ' .
                (!empty($threeDSData) ? 'present' : 'missing') . ', ipgId: ' .
                (!empty($ipgId) ? 'present' : 'missing'));
                return $this->redirectFactory->create()->setPath('checkout/cart');
            }

            // Extract hosts from 3DS data for CSP policies
            $hosts = $this->extractHostsFromThreeDSData($threeDSData);

            // Add dynamic CSP policies
            $this->addCspPolicies($threeDSData);

            $redirectUrl = $this->urlBuilder->getUrl('lloyds/paymentjs/transactionNotificationUrl', [
                'ipg_id' => $ipgId,
                '_secure' => true
            ]);

            $htmlContent = $this->loadTemplate('/frontend/web/template/threeds_template.html', [
                '{THREEDS_DATA}' => $threeDSData,
                '{REDIRECT_URL}' => $redirectUrl,
                '{PROCESSING_MESSAGE}' => __('Processing your payment...'),
                '{IPG_ID}' => $ipgId,
                '{ORDER_ID}' => $orderId ?: ''
            ]);

            $resultRaw = $this->resultRawFactory->create();

            $resultRaw->setHeader('Content-Type', 'text/html; charset=UTF-8');
            // Remove X-Frame-Options to allow embedding, rely on CSP frame-ancestors instead
            // X-Frame-Options: ALLOWALL is non-standard and should not be used
            $resultRaw->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
            $resultRaw->setHeader('Pragma', 'no-cache');
            $resultRaw->setHeader('Expires', '0');
            $resultRaw->setHeader('X-Content-Type-Options', 'nosniff');
            $resultRaw->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

            // Build dynamic CSP header with extracted hosts
            $frameAncestors = "'self'";
            if (!empty($hosts)) {
                $frameAncestors .= ' ' . implode(' ', $hosts);
            }

            $resultRaw->setHeader('Content-Security-Policy', "frame-ancestors " . $frameAncestors . ";");
            $resultRaw->setContents($htmlContent);
            return $resultRaw;

        } catch (\Exception $e) {
            $this->helper->addLog('ThreeDSFrame Error: ' . $e->getMessage());

            try {
                $errorContent = $this->loadTemplate('/frontend/web/template/error_template.html', [
                    '{ERROR_MESSAGE}' => __('Payment processing failed. Please try again or contact store support.'),
                    '{REDIRECT_URL}' => $this->urlBuilder->getUrl('checkout/cart')
                ]);

                $resultRaw = $this->resultRawFactory->create();
                $resultRaw->setHeader('Content-Type', 'text/html; charset=UTF-8');
                $resultRaw->setContents($errorContent);
                return $resultRaw;

            } catch (\Exception $fallbackException) {
                $this->helper->addLog('Error template also failed: ' . $fallbackException->getMessage());
                return $this->redirectFactory->create()->setPath('checkout/cart');
            }
        }
    }

    /**
     * Sanitize 3DS HTML content while preserving form functionality
     *
     * @param string $html
     * @return string
     */
    private function sanitizeThreeDSHtml($html)
    {
       // Allow only specific HTML tags that are needed for 3DS forms
        $allowedTags = '<form><input><button><iframe><div><span><p><br><script>';
    
        $dangerousPatterns = [
            '/on\w+\s*=\s*["\'][^"\']*["\']/i',
            '/javascript\s*:/i',
            '/<script[^>]*>.*?<\/script>/is'
        ];
    
        $is3DSContent = false;
        if (preg_match('/action\s*=\s*["\']https?:\/\/[^"\']+["\']/', $html) &&
            preg_match('/<form[^>]*>/', $html)) {
            $is3DSContent = true;
        }
    
        if (!$is3DSContent) {
            return $this->escaper->escapeHtml($html);
        }
    
        foreach ($dangerousPatterns as $pattern) {
            // Skip removing script tags if they contain 3DS-specific code
            if ($pattern === '/<script[^>]*>.*?<\/script>/is') {
                // Check if script contains auto-submit code which is common in 3DS
                if (preg_match('/<script[^>]*>.*?(submit\(\)|form\.submit).*?<\/script>/is', $html)) {
                    continue; // Keep legitimate 3DS auto-submit scripts
                }
            }
            $html = preg_replace($pattern, '', $html);
        }
    
        // Strip tags but allow necessary ones for 3DS
        $html = strip_tags($html, $allowedTags);

        // Ensure URLs in form actions are properly encoded
        $html = preg_replace_callback(
            '/action\s*=\s*["\']([^"\']+)["\']/i',
            function ($matches) {
                $url = $matches[1];
                // Validate URL and encode special characters
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    return 'action="' . $this->escaper->escapeHtml($url) . '"';
                }
                return $matches[0];
            },
            $html
        );

        return $html;
    }

    /**
     * Extract hosts from 3DS data
     *
     * @param string $threeDSData
     * @return array
     */
    private function extractHostsFromThreeDSData($threeDSData)
    {
        $urls = [];
        $hosts = [];
    
        try {
            // Extract URLs from action attributes
            if (preg_match_all('/action=["\']([^"\']+)["\']/', $threeDSData, $matches)) {
                $urls = array_merge($urls, $matches[1]);
            }
    
            // Extract any other URLs in the content
            if (preg_match_all('/https?:\/\/[^\s"\'<>]+/', $threeDSData, $matches)) {
                $urls = array_merge($urls, $matches[0]);
            }
    
            // Extract unique hosts from URLs using regex
            foreach ($urls as $url) {
                // Match protocol and host: https://example.com or http://subdomain.example.com
                if (preg_match('/^(https?:\/\/[^\/\s?#]+)/i', $url, $hostMatches)) {
                    $host = $hostMatches[1];
                    
                    if (!in_array($host, $hosts, true)) {
                        $hosts[] = $host;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->helper->addLog('Error extracting hosts from 3DS data: ' . $e->getMessage());
        }
    
        return $hosts;
    }

    /**
     * Add CSP policies to array
     *
     * @param string $threeDSData
     */
    private function addCspPolicies($threeDSData)
    {
        try {
            $hosts = $this->extractHostsFromThreeDSData($threeDSData);

            foreach ($hosts as $host) {
                // Frame-src policy to allow iframe loading
                $frameSrcPolicy = new FetchPolicy(
                    'frame-src',
                    false,
                    [$host],
                    [],
                    true,
                    false,
                    false,
                    [],
                    [],
                    false,
                    false
                );

                // Form-action policy to allow form submission
                $formActionPolicy = new FetchPolicy(
                    'form-action',
                    false,
                    [$host],
                    [],
                    true,
                    false,
                    false,
                    [],
                    [],
                    false,
                    false
                );

                // Connect-src policy for AJAX requests
                $connectSrcPolicy = new FetchPolicy(
                    'connect-src',
                    false,
                    [$host],
                    [],
                    true,
                    false,
                    false,
                    [],
                    [],
                    false,
                    false
                );
                $this->dynamicCollector->add($frameSrcPolicy);
                $this->dynamicCollector->add($formActionPolicy);
                $this->dynamicCollector->add($connectSrcPolicy);
            }

        } catch (\Exception $e) {
            $this->helper->addLog('CSP Policy Error: ' . $e->getMessage());
        }
    }
}
