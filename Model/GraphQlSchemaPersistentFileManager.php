<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model;

use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class GraphQlSchemaPersistentFileManager
{
    protected Filesystem $filesystem;

    protected DirectoryList $directoryList;

    protected ReaderInterface $reader;

    protected Json $serializer;

    protected LoggerInterface $logger;

    protected $schemaData;

    public const GRAPHQL_SCHEMA_FILENAME = 'graphql.schema';

    /**
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     * @param Json $serializer
     * @param ReaderInterface $reader
     * @param LoggerInterface $logger
     */
    public function __construct(
        Filesystem $filesystem,
        DirectoryList $directoryList,
        Json $serializer,
        ReaderInterface $reader,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->reader = $reader;
        $this->logger = $logger;
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
     * @return void
     */
    public function setSchemaData($schemaData): void
    {
        $this->schemaData = $schemaData;
    }

    /**
     * @return void
     */
    public function createCachedSchemaFile(): void
    {
        try {
            $this->getWriteDirectory()->writeFile(self::GRAPHQL_SCHEMA_FILENAME, $this->getSchemaData());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @return false|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getCachedSchemaData()
    {
        if ($this->getWriteDirectory()->isExist(self::GRAPHQL_SCHEMA_FILENAME)) {
            return $this->getWriteDirectory()->readFile(self::GRAPHQL_SCHEMA_FILENAME);
        }
        return false;
    }

    /**
     * @return void
     */
    public function deleteCachedSchemaFile(): void
    {
        try {
            $this->getWriteDirectory()->delete(self::GRAPHQL_SCHEMA_FILENAME);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function refreshCachedSchemaFile(): void
    {
        $this->deleteCachedSchemaFile();
        $this->createCachedSchemaFile();
    }

    /**
     * @return WriteInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getWriteDirectory(): WriteInterface
    {
        return $this->filesystem->getDirectoryWrite($this->directoryList::GENERATED);
    }
}
