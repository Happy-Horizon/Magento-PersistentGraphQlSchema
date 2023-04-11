<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model;

use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Serialize\Serializer\Json;

class GraphQlSchemaPersistentFileManager
{
    protected WriteInterface $filesystem;
    protected DirectoryList $directoryList;
    protected ReaderInterface $reader;
    protected Json $serializer;
    protected string $schemaData;
    public const GRAPHQL_SCHEMA_FILENAME = 'graphql.schema';

    public function __construct(
        Filesystem $filesystem,
        DirectoryList $directoryList,
        Json $serializer,
        ReaderInterface $reader
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem->getDirectoryWrite($this->directoryList::GENERATED);
        $this->serializer = $serializer;
        $this->reader = $reader;
    }

    /**
     * @return bool|string
     */
    public function getSchemaData()
    {
        if (!$this->schemaData) {
            $this->schemaData = $this->serializer->serialize($this->reader->read());
        }

        return $this->schemaData;
    }

    /**
     * @param $schemaData
     * @return $this
     */
    public function setSchemaData($schemaData)
    {
        $this->schemaData = $schemaData;
        return $this;
    }

    /**
     * @return void
     */
    public function createCachedSchemaFile(): void
    {
        try {
            $this->filesystem->writeFile(self::GRAPHQL_SCHEMA_FILENAME, $this->getSchemaData());
        } catch (\Exception $e) {
        }
    }

    /**
     * @return false|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getCachedSchemaData()
    {
        if ($this->filesystem->isExist(self::GRAPHQL_SCHEMA_FILENAME)) {
            return $this->filesystem->readFile(self::GRAPHQL_SCHEMA_FILENAME);
        }
        return false;
    }

    /**
     * @return $this
     */
    public function deleteCachedSchemaFile()
    {
        try {
            $this->filesystem->delete(self::GRAPHQL_SCHEMA_FILENAME);
        } catch (\Exception $e) {
        }
        return $this;
    }

    /**
     * @return void
     */
    public function refreshCachedSchemaFile(): void
    {
        $this->getSchemaData();
        $this->deleteCachedSchemaFile();
        $this->createCachedSchemaFile();
    }
}
