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

    protected Json $serializer;

    protected LoggerInterface $logger;

    protected ReaderInterface $reader;

    protected $schemaData;

    public const GRAPHQL_SCHEMA_FILENAME = 'graphql.schema';

    /**
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     * @param Json $serializer
     * @param LoggerInterface $logger
     * @param ReaderInterface $reader
     */
    public function __construct(
        Filesystem $filesystem,
        DirectoryList $directoryList,
        Json $serializer,
        LoggerInterface $logger,
        ReaderInterface $reader
    ) {
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->logger = $logger;

        // Injected reader is virtualType Magento\Framework\GraphQlSchemaStitching\Reader; see ./etc/di.xml
        $this->reader = $reader;
    }

    /**
     * Get graphql.schema data
     *
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
     * Get cached schema if available
     *
     * @return false|string
     */
    public function getCachedSchemaData()
    {
        if (!$this->getWriteDirectory()->isExist(self::GRAPHQL_SCHEMA_FILENAME)) {
            return false;
        }

        try {
            return $this->getWriteDirectory()->readFile(self::GRAPHQL_SCHEMA_FILENAME);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
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
     * Refresh ./generated/graphql.schema file
     *
     * @return void
     */
    public function refreshCachedSchemaFile(): void
    {
        /**
         * Decided to not compare contents of cached file if it is found.
         * The most time is spend on "$this->reader->read()", making a refresh of the file equally fast.
         */

        if ($this->getCachedSchemaData()) {
            $this->deleteCachedSchemaFile();
        }
        $this->createCachedSchemaFile();
    }

    /**
     * Get write directory (./generated)
     *
     * @return WriteInterface
     */
    private function getWriteDirectory(): WriteInterface
    {
        return $this->filesystem->getDirectoryWrite($this->directoryList::GENERATED);
    }
}
