<?php

declare(strict_types=1);

namespace Amasty\Mage248Fix\Setup\Patch\Data;

use Amasty\Base\Helper\Deploy;
use Amasty\Base\Model\FilesystemProvider;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Module\Dir;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class OverrideResponsiveLessLib implements DataPatchInterface, PatchRevertableInterface
{
    public const LIB_PATH = 'lib/web/css/source/lib/_responsive.less';

    /**
     * @var FilesystemProvider
     */
    private FilesystemProvider $filesystemProvider;

    /**
     * @var Dir
     */
    private Dir $moduleDir;

    public function __construct(
        FilesystemProvider $filesystemProvider,
        Dir $moduleDir
    ) {
        $this->filesystemProvider = $filesystemProvider;
        $this->moduleDir = $moduleDir;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        try {
            $filesystem = $this->filesystemProvider->get();
            $rootRead = $filesystem->getDirectoryRead(DirectoryList::ROOT);
            $modulePath = $this->moduleDir->getDir('Amasty_Mage248Fix');
            $relativeFilePath = $rootRead->getRelativePath($modulePath . '/' . self::LIB_PATH);
            $rootWrite = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
            $rootWrite->renameFile(self::LIB_PATH, self::LIB_PATH . '_bk');
            $rootWrite->copyFile($relativeFilePath, self::LIB_PATH);
            $rootWrite->changePermissions(self::LIB_PATH, Deploy::DEFAULT_FILE_PERMISSIONS);
            $rootWrite->changePermissions(self::LIB_PATH . '_bk', Deploy::DEFAULT_FILE_PERMISSIONS);
        } catch (FileSystemException $e) {
            $this->rollback();
        }
    }

    /**
     * Rename the original responsive.less file if current responsive.less is overridden by this patch
     */
    public function revert(): void
    {
        try {
            $filesystem = $this->filesystemProvider->get();
            $rootRead = $filesystem->getDirectoryRead(DirectoryList::ROOT);
            $modulePath = $this->moduleDir->getDir('Amasty_Mage248Fix');
            $relativeFilePath = $rootRead->getRelativePath($modulePath . '/' . self::LIB_PATH);
            if ($rootRead->isExist(self::LIB_PATH . '_bk')
                && $rootRead->readFile($relativeFilePath) === $rootRead->readFile(self::LIB_PATH)
            ) {
                $rootWrite = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
                $rootWrite->delete(self::LIB_PATH);
                $rootWrite->renameFile(self::LIB_PATH . '_bk', self::LIB_PATH);
            }
        // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (FileSystemException $e) {
            // Catch for Cloud environment
        }
    }

    private function rollback(): void
    {
        try {
            $filesystem = $this->filesystemProvider->get();
            $rootRead = $filesystem->getDirectoryRead(DirectoryList::ROOT);

            if ($rootRead->isExist(self::LIB_PATH . '_bk')) {
                $rootWrite = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
                $rootWrite->renameFile(self::LIB_PATH . '_bk', self::LIB_PATH);
            }
        // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (FileSystemException $e) {
            // Catch for Cloud environment
        }
    }
}
