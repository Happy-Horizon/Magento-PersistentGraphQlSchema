<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Cache;

use HappyHorizon\PersistentGraphQlSchema\Model\GraphQlSchemaPersistentFileManager;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\App\Cache\Type\FrontendPool;

class GraphQlSchemaCache extends TagScope implements CacheInterface
{
    protected GraphQlSchemaPersistentFileManager $graphQlSchemaPersistentFileManager;
    const TYPE_IDENTIFIER = 'graphqlschema_cache';
    const CACHE_TAG = 'GRAPHQLSCHEMA_CACHE';

    /**
     * @param FrontendPool $cacheFrontendPool
     * @param GraphQlSchemaPersistentFileManager $graphQlSchemaPersistentFileManager
     */
    public function __construct(
        FrontendPool $cacheFrontendPool,
        GraphQlSchemaPersistentFileManager $graphQlSchemaPersistentFileManager
    ) {
        $this->graphQlSchemaPersistentFileManager = $graphQlSchemaPersistentFileManager;
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }

    /**
     * @param $identifier
     * @return bool|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function load($identifier)
    {
        // Serve cached schema data if available
        if ($cachedSchemaData = $this->graphQlSchemaPersistentFileManager->getCachedSchemaData()) {
            return $cachedSchemaData;
        }
        return parent::load($identifier);
    }

    /**
     * @param $data
     * @param $identifier
     * @param array $tags
     * @param $lifeTime
     * @return bool
     */
    public function save($data, $identifier, array $tags = [], $lifeTime = null)
    {
        $this->graphQlSchemaPersistentFileManager->setSchemaData($data);
        $this->graphQlSchemaPersistentFileManager->createCachedSchemaFile();
        return parent::save($data, $identifier, $tags, $lifeTime);
    }

    /**
     * @param $identifier
     * @return bool True if no problem
     */
    public function remove($identifier)
    {
        return $this->clean();
    }

    /**
     * @param $mode
     * @param array $tags
     * @return bool True if no problem
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = [])
    {
        // Contains smart refresh of the graphql.schema
        $this->graphQlSchemaPersistentFileManager->refreshCachedSchemaFile();
        return true;
    }
}
