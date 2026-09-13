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
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Escaper;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data;

/**
 * Renders the 3DS challenge (cReq/TermURL -> bank ACS URL) on a same-origin page
 * and dynamically whitelists the ACS host in the CSP "form-action" policy, so the
 * cross-origin submit is allowed. This mirrors the ThreeDSFrame approach and
 * replaces the previous client-side formBuilder(...).submit() which the static
 * checkout CSP blocked.
 */
class ChallengeFrame implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
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
     * @var DynamicCollector
     */
    private $dynamicCollector;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param RequestInterface $request
     * @param RawFactory $resultRawFactory
     * @param RedirectFactory $redirectFactory
     * @param ModuleDirReader $moduleDirReader
     * @param File $fileDriver
     * @param Escaper $escaper
     * @param DynamicCollector $dynamicCollector
     * @param SerializerInterface $serializer
     * @param EncryptorInterface $encryptor
     * @param Data $helper
     */
    public function __construct(
        RequestInterface $request,
        RawFactory $resultRawFactory,
        RedirectFactory $redirectFactory,
        ModuleDirReader $moduleDirReader,
        File $fileDriver,
        Escaper $escaper,
        DynamicCollector $dynamicCollector,
        SerializerInterface $serializer,
        EncryptorInterface $encryptor,
        Data $helper
    ) {
        $this->request = $request;
        $this->resultRawFactory = $resultRawFactory;
        $this->redirectFactory = $redirectFactory;
        $this->moduleDirReader = $moduleDirReader;
        $this->fileDriver = $fileDriver;
        $this->escaper = $escaper;
        $this->dynamicCollector = $dynamicCollector;
        $this->serializer = $serializer;
        $this->encryptor = $encryptor;
        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Render the 3DS challenge auto-submit form.
     */
    public function execute()
    {
        $this->helper->addLog('ChallengeFrame started');

        try {
            $challenge = $this->resolveChallengeData();
            $acsURL = $challenge['acsURL'];

            // Dynamically allow the bank ACS host so the cross-origin submit is not blocked.
            $this->addCspPolicies($acsURL);

            $htmlContent = $this->loadTemplate('/frontend/web/template/challenge_template.html', [
                '{ACS_URL}' => $this->escaper->escapeUrl($acsURL),
                '{CREQ}' => $this->escaper->escapeHtmlAttr($challenge['creq']),
                '{TERM_URL}' => $this->escaper->escapeHtmlAttr($challenge['TermURL']),
                '{PROCESSING_MESSAGE}' => $this->escaper->escapeHtml(__('Processing your payment...')),
            ]);

            $resultRaw = $this->resultRawFactory->create();
            $resultRaw->setHeader('Content-Type', 'text/html; charset=UTF-8');
            $resultRaw->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
            $resultRaw->setHeader('Pragma', 'no-cache');
            $resultRaw->setHeader('Expires', '0');
            $resultRaw->setHeader('X-Content-Type-Options', 'nosniff');
            $resultRaw->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
            $resultRaw->setContents($htmlContent);

            return $resultRaw;
        } catch (\Exception $e) {
            $this->helper->addLog('ChallengeFrame Error: ' . $e->getMessage());
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }
    }

    /**
     * Decrypt and validate the challenge payload built by Helper::buildChallengeFormData().
     *
     * @return array{acsURL: string, creq: string, TermURL: string}
     * @throws \Exception
     */
    private function resolveChallengeData(): array
    {
        $blob = (string) $this->request->getParam('d');
        if ($blob === '') {
            throw new \Exception('Missing challenge payload');
        }

        $decoded = $this->encryptor->decrypt($blob);
        if ($decoded === '') {
            throw new \Exception('Unable to decrypt challenge payload');
        }

        $data = $this->serializer->unserialize($decoded);
        $acsURL = isset($data['acsURL']) ? (string) $data['acsURL'] : '';
        $creq = isset($data['creq']) ? (string) $data['creq'] : '';
        $termURL = isset($data['TermURL']) ? (string) $data['TermURL'] : '';

        if ($acsURL === '' || $creq === '' || $termURL === '') {
            throw new \Exception('Incomplete challenge payload');
        }

        if (!filter_var($acsURL, FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $acsURL)) {
            throw new \Exception('Invalid ACS URL');
        }

        return [
            'acsURL' => $acsURL,
            'creq' => $creq,
            'TermURL' => $termURL,
        ];
    }

    /**
     * Load and populate an HTML template shipped with the module.
     *
     * @param string $templatePath
     * @param array $replacements
     * @return string
     * @throws \Exception
     */
    private function loadTemplate(string $templatePath, array $replacements = []): string
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
            throw new \Exception('Challenge template file not found');
        }

        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, (string) $value, $content);
        }

        return $content;
    }

    /**
     * Add dynamic CSP policies allowing a submit/navigation to the bank ACS host.
     *
     * @param string $acsURL
     */
    private function addCspPolicies(string $acsURL): void
    {
        try {
            if (!preg_match('~^(https://[^/\s?#]+)~i', $acsURL, $matches)) {
                return;
            }
            $host = $matches[1];

            foreach (['form-action', 'frame-src', 'connect-src'] as $directive) {
                $policy = new FetchPolicy(
                    $directive,
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
                $this->dynamicCollector->add($policy);
            }
        } catch (\Exception $e) {
            $this->helper->addLog('ChallengeFrame CSP Policy Error: ' . $e->getMessage());
        }
    }
}