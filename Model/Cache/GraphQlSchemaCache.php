<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Cache;

use HappyHorizon\PersistentGraphQlSchema\Model\GraphQlSchemaPersistentFileManager;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\App\Cache\Type\FrontendPool;

class GraphQlSchemaCache extends TagScope implements CacheInterface
{
    protected GraphQlSchemaPersistentFileManager $graphQlSchemaPersistentFileManager;

    protected StateInterface $cacheState;

    const TYPE_IDENTIFIER = 'graphqlschema_cache';
    const CACHE_TAG = 'GRAPHQLSCHEMA_CACHE';

    /**
     * @param FrontendPool $cacheFrontendPool
     * @param GraphQlSchemaPersistentFileManager $graphQlSchemaPersistentFileManager
     * @param StateInterface $cacheState
     */
    public function __construct(
        FrontendPool $cacheFrontendPool,
        GraphQlSchemaPersistentFileManager $graphQlSchemaPersistentFileManager,
        StateInterface $cacheState
    ) {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
        $this->graphQlSchemaPersistentFileManager = $graphQlSchemaPersistentFileManager;
        $this->cacheState = $cacheState;
    }

    /**
     * @param $identifier
     * @return bool|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function load($identifier)
    {
        if (!$this->cacheState->isEnabled(self::TYPE_IDENTIFIER)) {
            return parent::load($identifier);
        }

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
        if (!$this->cacheState->isEnabled(self::TYPE_IDENTIFIER)) {
            return parent::save($data, $identifier, $tags, $lifeTime);
        }

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
        if (!$this->cacheState->isEnabled(self::TYPE_IDENTIFIER)) {
            return parent::remove($identifier);
        }

        return $this->clean();
    }

    /**
     * @param $mode
     * @param array $tags
     * @return bool True if no problem
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = [])
    {
        if (!$this->cacheState->isEnabled(self::TYPE_IDENTIFIER)) {
            return parent::clean($mode, $tags);
        }

        // Contains smart refresh of the graphql.schema
        $this->graphQlSchemaPersistentFileManager->refreshCachedSchemaFile();
        return true;
    }
}
