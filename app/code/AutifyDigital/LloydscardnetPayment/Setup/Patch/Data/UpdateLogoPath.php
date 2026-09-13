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

namespace AutifyDigital\LloydscardnetPayment\Setup\Patch\Data;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;

class UpdateLogoPath implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ComponentRegistrarInterface
     */
    protected $componentRegistrar;

    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    protected $fileDriver;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * UpdateLogoPaths constructor.
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     * @param ResourceConnection $resourceConnection
     * @param File $fileDriver
     * @param ComponentRegistrarInterface $componentRegistrar
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        ResourceConnection $resourceConnection,
        File $fileDriver,
        ComponentRegistrarInterface $componentRegistrar
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->fileDriver = $fileDriver;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->resourceConnection = $resourceConnection;
        $this->componentRegistrar = $componentRegistrar;
    }

    /**
     * Apply the patch
     *
     * @return \Magento\Framework\Setup\Patch\PatchInterface
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        try {
            $imgPaths = [
                'lcnetredirect' => 'lloyds_logo.png',
                'lcnetpaymentjs' => 'lloyds_logo.png',
                'cardnetapplepay' => 'ApplePay.png',
                'cardnetgooglepay' => 'GPay.png'
            ];

            $modulePath = $this->componentRegistrar->getPath(
                ComponentRegistrar::MODULE,
                'AutifyDigital_LloydscardnetPayment'
            );

            foreach ($imgPaths as $paymentMethod => $image) {
                $sourceImagePath = $modulePath . '/view/frontend/web/images/' . $image;
                $destinationDir = $this->directoryList->getRoot() .
                    '/pub/media/lloydscardnet/default';
                $destinationImagePath = $destinationDir . '/' . $image;

                if ($this->fileDriver->isExists($sourceImagePath)) {
                    if (!$this->fileDriver->isDirectory($destinationDir)) {
                        $this->fileDriver->createDirectory($destinationDir, 0777);
                    }
                    $this->fileDriver->copy($sourceImagePath, $destinationImagePath);
                }
                $configPath = 'payment/' . $paymentMethod . '/payment_logo';
                $this->updateLogoPathInConfig($configPath, 'default/' . $image);
            }
        } catch (\Exception $e) {
            $this->moduleDataSetup->endSetup();
            throw $e;
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * Update the logo path in core_config_data
     *
     * @param string $configPath
     * @param string $logoPath
     */
    protected function updateLogoPathInConfig($configPath, $logoPath)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('core_config_data');

        $select = $connection->select()
            ->from($tableName)
            ->where('path = ?', $configPath);

        $row = $connection->fetchRow($select);

        if ($row) {
            $connection->update(
                $tableName,
                ['value' => $logoPath],
                ['path = ?' => $configPath]
            );
        } else {
            $connection->insert(
                $tableName,
                [
                    'scope' => 'default',
                    'scope_id' => 0,
                    'path' => $configPath,
                    'value' => $logoPath
                ]
            );
        }
    }

    /**
     * Get Dependencies
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Revert
     */
    public function revert()
    {
        return; // phpcs:ignore
    }

    /**
     * Aliases
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
