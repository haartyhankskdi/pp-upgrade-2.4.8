<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogImport\Model\WordpressV2Import;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

// phpcs:disable Magento2.Functions.DiscouragedFunction
class DownloadFilesFromContent
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var DOMDocumentFactory
     */
    protected $domDocumentFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param RequestInterface $request
     * @param Filesystem $filesystem
     * @param DomDocumentFactory $domDocumentFactory
     */
    public function __construct(
        RequestInterface $request,
        Filesystem $filesystem,
        DOMDocumentFactory $domDocumentFactory
    ) {
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->domDocumentFactory = $domDocumentFactory;
    }

    /**
     * Processes the given HTML content by replacing URLs with localized file paths.
     *
     * @param string $html
     * @return string
     * @throws FileSystemException
     */
    public function execute(string $html)
    {
        $urls = $this->getUrlsFromContent($html);
        $pathsToFilesOnOurInstance = [];

        foreach ($urls as $url) {
            $urlToDownload = $url;
            $pathsToFilesOnOurInstance[$this->deleteAllGetParametersFromUrl($url)] =
                '{{media url=' . $this->downloadFileByUrl($urlToDownload, '/images/') . '}}';
        }
        $html = str_replace(
            array_keys($pathsToFilesOnOurInstance),
            array_values($pathsToFilesOnOurInstance),
            $html
        );

        return $html;
    }

    /**
     * Extracts and returns a list of URLs from the given HTML content by parsing image and audio tags.
     *
     * @param string $html
     * @return array
     */
    protected function getUrlsFromContent(string $html)
    {
        $dom = $this->domDocumentFactory->create();

        try {
            $dom->loadHTML($html);
        } catch (\Exception $e) {
            return ' Error: ' . $e->getMessage();
        }

        $imageTags = $dom->getElementsByTagName('img');
        $fileSrcList = $this->getSrcs($imageTags);

        $audioTags = $dom->getElementsByTagName('audio');

        return array_merge($fileSrcList, $this->getSrcs($audioTags));
    }

    /**
     * Extracts and returns a list of URLs from the given DOMNodeList.
     *
     * @param \DOMNodeList $files
     * @return array
     */
    protected function getSrcs(\DOMNodeList $files): array
    {
        $srcs = [];
        foreach ($files as $file) {
            $srcs[] = $file->getAttribute('src');
        }
        return $srcs;
    }

    /**
     * Deletes all GET parameters from the given URL.
     *
     * @param string $url
     * @return string
     */
    protected function deleteAllGetParametersFromUrl(string $url): string
    {
        try {
            $parsedUrl = parse_url($url);
        } catch (\Exception $e) {
            return $url;
        }

        if (!isset($parsedUrl['scheme'], $parsedUrl['host'], $parsedUrl['path'])) {
            return $url;
        }

        return $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
    }

    /**
     * Downloads a file from the given URL and saves it to the media directory.
     *
     * @param string $url
     * @param string $subPath
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function downloadFileByUrl(string $url, string $subPath): string
    {
        if (!$url || !$this->isValidURL($url)) {
            return '';
        }

        $url = str_replace(' ', '%20', $url);

        try {

            $contents = file_get_contents($url);
        } catch (\Exception $e) {
            return ' Error: ' . $e->getMessage();
        }

        if (!$contents) {
            return '';
        }

        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $filename = $this->getLastTermFromUrl($url);
        $filePath = "magefan_blog{$subPath}{$filename}";
        try {
            $mediaDir->writeFile($filePath, $contents);
        } catch (\Exception $e) {
            return ' File write error: ' . $e->getMessage();
        }

        return $filePath;
    }

    /**
     * Returns the last term from the given URL.
     *
     * @param string $url
     * @return string
     */
    protected function getLastTermFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        return basename($path);
    }

    /**
     * Checks if the given URL is valid.
     *
     * @param string $url
     * @return bool
     */
    protected function isValidURL(string $url): bool
    {
        $urlComponents = parse_url($url);
        return $urlComponents !== false
            && isset($urlComponents['scheme'], $urlComponents['host'])
            && $urlComponents['scheme'] === 'https';
    }
}
