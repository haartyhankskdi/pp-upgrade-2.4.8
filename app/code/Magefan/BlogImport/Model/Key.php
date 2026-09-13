<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;

class Key
{
    public const IMPORT_KEY_PATH = 'mfblog/general/import_key';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param WriterInterface $configWriter
     * @param ResourceConnection $resource
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        WriterInterface $configWriter,
        ResourceConnection $resource,
        EncryptorInterface $encryptor
    ) {
        $this->configWriter = $configWriter;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->encryptor = $encryptor;
    }

    /**
     * Validates the given key.
     *
     * @param string $key
     * @return bool
     */
    public function validate(string $key): bool
    {
        if (!$key) {
            return false;
        }

        if ($key === $this->get()) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve the import key
     *
     * @return string
     */
    public function get(): string
    {
        $select = $this->connection->select()
            ->from($this->resource->getTableName('core_config_data'), 'value')
            ->where('path = ?', self::IMPORT_KEY_PATH);

        $key = (string)$this->connection->fetchOne($select);

        $key = (!$key || $this->isExpired())
            ? $this->generate()
            : $this->encryptor->decrypt($key);

        return $key;
    }

    /**
     * Check if the import key is expired
     *
     * @return bool
     */
    private function isExpired(): bool
    {
        $tableName = $this->resource->getTableName('core_config_data');

        $deletedRows = $this->connection->delete($tableName, [
                'path like ?' => self::IMPORT_KEY_PATH,
                'updated_at < ?' => (new \DateTime())->modify('-1 day')->format('Y-m-d H:i:s')
            ]);

        return (bool) $deletedRows;
    }

    /**
     * Generates a random string of specified length
     *
     * @param int $length
     * @return string
     */
    private function getRandomString(int $length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $string;
    }

    /**
     * Generate the import key
     *
     * @return string
     */
    private function generate(): string
    {
        $key = $this->getRandomString(20);

        $this->configWriter->save(
            self::IMPORT_KEY_PATH,
            $this->encryptor->encrypt($key),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        return $key;
    }
}
