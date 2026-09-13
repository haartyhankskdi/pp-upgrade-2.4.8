<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer;

use Amasty\Customform\Api\Answer\AttachedFileDataProviderInterface;
use Amasty\Customform\Api\Answer\GetAttachedFileUrlInterface;
use Amasty\Customform\Exceptions\AttachedFileDoesNotExistsException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File as FileDriver;
use Magento\MediaStorage\Model\File\Uploader;

class AttachedFileDataProvider implements AttachedFileDataProviderInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var GetAttachedFileUrlInterface
     */
    private $getSecuredFileUrl;

    /**
     * @var FileDriver
     */
    private $fileDriver;

    public function __construct(
        Filesystem $filesystem,
        GetAttachedFileUrlInterface $getSecuredFileUrl,
        FileDriver $fileDriver
    ) {
        $this->filesystem = $filesystem;
        $this->getSecuredFileUrl = $getSecuredFileUrl;
        $this->fileDriver = $fileDriver;
    }

    public function getPath(string $fileName): string
    {
        $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath();

        return sprintf(
            '%s%s%s%s%s',
            rtrim($varDir, DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR,
            GetDownloadPath::AMASTY_CUSTOMFORM_VAR_PATH,
            DIRECTORY_SEPARATOR,
            Uploader::getCorrectFileName($fileName)
        );
    }

    public function getContents(string $fileName): string
    {
        $filePath = $this->getPath($fileName);

        if ($this->fileDriver->fileExists($filePath)) {
            return $this->fileDriver->read($filePath);
        } else {
            throw AttachedFileDoesNotExistsException::forFile($fileName);
        }
    }

    public function getUrl(string $fileName, ?int $storeId = null): string
    {
        return $this->getSecuredFileUrl->execute($fileName, $storeId);
    }
}
