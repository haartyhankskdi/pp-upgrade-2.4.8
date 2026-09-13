<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Response;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;

class InMemoryFileResponse extends Raw
{
    /**
     * @var string
     */
    private $contentType;

    /**
     * @var string
     */
    private $fileName;

    public function renderResult(ResponseInterface $response)
    {
        $this->renderHeaders();

        return parent::renderResult($response);
    }

    private function renderHeaders(): void
    {
        $contentType = $this->contentType ?: 'application/octet-stream';
        $this->setHeader('Pragma', 'public', true);
        $this->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $this->setHeader('Content-type', $contentType, true);
        $this->setHeader('Last-Modified', date('r'), true);
        $this->setHeader(
            'Content-Disposition',
            sprintf('attachment; filename=%s', $this->getFileName()),
            true
        );
    }

    private function getFileName(): string
    {
        if ($this->fileName === null) {
            $this->fileName = uniqid();
        }

        return $this->fileName;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function _resetState(): void
    {
        $this->fileName = null;
        $this->contentType = null;
    }
}
