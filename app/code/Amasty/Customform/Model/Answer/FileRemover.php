<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Api\Answer\AttachedFileDataProviderInterface;
use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Submit;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

class FileRemover
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var AttachedFileDataProviderInterface
     */
    private $attachedFileDataProvider;

    public function __construct(
        Serializer $serializer,
        File $fileDriver,
        AttachedFileDataProviderInterface $attachedFileDataProvider
    ) {
        $this->serializer = $serializer;
        $this->fileDriver = $fileDriver;
        $this->attachedFileDataProvider = $attachedFileDataProvider;
    }

    public function execute(AnswerInterface $answer)
    {
        $fieldsData = $this->serializer->unserialize($answer->getResponseJson());

        foreach ($fieldsData as $field) {
            if ($field[Submit::TYPE] == 'file') {
                if (!is_array($field[Submit::VALUE])) {
                    $field[Submit::VALUE] = [$field[Submit::VALUE]];
                }

                foreach ($field[Submit::VALUE] as $file) {
                    try {
                        if (!empty($file)) {
                            $this->deleteFile($this->attachedFileDataProvider->getPath($file));
                        }
                    } catch (FileSystemException $exception) {
                        continue;
                    }
                }
            }
        }
    }

    /**
     * @param string $path
     *
     * @throws FileSystemException
     */
    private function deleteFile(string $path): void
    {
        if ($this->fileDriver->isExists($path)) {
            $this->fileDriver->deleteFile($path);
        }
    }
}
