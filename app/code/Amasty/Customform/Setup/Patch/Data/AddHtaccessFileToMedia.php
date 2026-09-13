<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Setup\Patch\Data;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddHtaccessFileToMedia implements DataPatchInterface
{
    public const FILE_PATH = '/pub/media/amasty/amcustomform/';
    public const FILE_NAME = '.htaccess';
    public const MODULE_NAME = 'Amasty_Customform';

    /**
     * @var File
     */
    private $fileIo;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;

    public function __construct(
        File $fileIo,
        Filesystem $filesystem,
        ComponentRegistrarInterface $componentRegistrar
    ) {
        $this->fileIo = $fileIo;
        $this->filesystem = $filesystem;
        $this->componentRegistrar = $componentRegistrar;
    }

    public function apply(): void
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        $source = $modulePath . self::FILE_PATH . self::FILE_NAME;
        $rootDir = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);

        if (!$this->canCopyFile($source, $rootDir)) {
            return;
        }

        $destination = $rootDir->getAbsolutePath(self::FILE_PATH . self::FILE_NAME);

        $this->fileIo->cp($source, $destination);
    }

    private function canCopyFile(string $source, ReadInterface $rootDir): bool
    {
        return $this->fileIo->fileExists($source) && $rootDir->isExist(self::FILE_PATH);
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
