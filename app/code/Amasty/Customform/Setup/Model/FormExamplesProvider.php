<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Setup\Model;

use Amasty\Base\Model\Serializer;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;

class FormExamplesProvider
{
    public const EXAMPLES_DIR = 'data/form/examples';

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var File
     */
    private $fileSystem;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        Reader $reader,
        File $fileSystem,
        Serializer $serializer
    ) {
        $this->reader = $reader;
        $this->fileSystem = $fileSystem;
        $this->serializer = $serializer;
    }

    public function getExampleFormsData(): iterable
    {
        foreach ($this->getFilesPaths() as $filesPath) {
            try {
                $fileContent = $this->fileSystem->read($filesPath);

                yield $this->serializer->unserialize($fileContent);
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * @return string[]
     */
    private function getFilesPaths(): array
    {
        $result = [];
        $moduleDir = $this->reader->getModuleDir('', 'Amasty_Customform');
        $examplesDir = $moduleDir . DIRECTORY_SEPARATOR . self::EXAMPLES_DIR;

        if ($this->fileSystem->fileExists($examplesDir, false)) {
            $files = $this->fileSystem->getDirectoriesList($examplesDir, GLOB_NOSORT);
            $result = array_filter($files, function ($path): bool {
                return (bool) preg_match('/^.*_form\.json$/m', $path);
            });
        }

        return $result;
    }
}
