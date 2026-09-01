<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Response;

use Amasty\Customform\Model\Zip\ZipStreamFactory;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\DateTime;

class ZipStreamOctetResponse extends Http
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var bool
     */
    private $headersInitialized = false;

    /**
     * @var iterable
     */
    private $zipContentSource = [];

    /**
     * @var ZipStreamFactory
     */
    private $zipStreamFactory;

    public function __construct(
        HttpRequest $request,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        Context $context,
        DateTime $dateTime,
        ZipStreamFactory $zipStreamFactory,
        ConfigInterface $sessionConfig = null
    ) {
        $this->initHeaders();
        $this->zipStreamFactory = $zipStreamFactory;

        parent::__construct(
            $request,
            $cookieManager,
            $cookieMetadataFactory,
            $context,
            $dateTime,
            $sessionConfig
        );
    }

    public function getFileName(): string
    {
        if ($this->fileName === null) {
            $this->fileName = sprintf('%s.zip', uniqid());
        }

        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    private function initHeaders(): void
    {
        if (!$this->headersInitialized) {
            $this->setHttpResponseCode(200);
            $this->setHeader('Content-type', 'application/x-zip', true);
            $this->setHeader('Pragma', 'public', true);
            $this->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
            $this->setHeader('Content-type', 'application/octet-stream', true);
            $this->setHeader('Last-Modified', date('r'), true);
            $this->setHeader('Content-Transfer-Encoding', 'binary', true);
            $this->headersInitialized = true;
        }
    }

    public function setZipContentSource(iterable $zipContentSource): void
    {
        $this->zipContentSource = $zipContentSource;
    }

    private function getZipContentSource(): iterable
    {
        return $this->zipContentSource;
    }

    public function sendContent(): ZipStreamOctetResponse
    {
        $this->setHeader(
            'Content-Disposition',
            sprintf('attachment; filename=%s', $this->getFileName()),
            true
        );
        $this->clearBody();
        $this->sendHeaders();
        $this->startSendingStream();

        return $this;
    }

    private function startSendingStream(): void
    {
        $zipStream = $this->zipStreamFactory->create($this->getFileName());

        foreach ($this->getZipContentSource() as $filename => $fileContent) {
            $zipStream->addFile($filename, $fileContent);
        }

        $zipStream->finish();
    }
}
