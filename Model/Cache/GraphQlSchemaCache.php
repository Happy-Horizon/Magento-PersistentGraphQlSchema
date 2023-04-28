<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Model\Cache;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Config\ReaderInterface;

class GraphQlSchemaCache extends TagScope implements CacheInterface
{
    protected FrontendPool $cacheFrontendPool;
    protected ReaderInterface $reader;

    const TYPE_IDENTIFIER = 'graphql_schema';
    const CACHE_TAG = 'GRAPHQL_SCHEMA';

    /**
     * @var bool $isCacheEnabled
     */
    private bool $isCacheEnabled;

    /**
     * @param FrontendPool $cacheFrontendPool
     * @param StateInterface $cacheState
     * @param ReaderInterface $reader
     */
    public function __construct(
        FrontendPool $cacheFrontendPool,
        StateInterface $cacheState,
        ReaderInterface $reader
    ) {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
        $this->isCacheEnabled = $cacheState->isEnabled(GraphQlSchemaCache::TYPE_IDENTIFIER);

        // Injected reader is virtualType Magento\Framework\GraphQlSchemaStitching\Reader; see ./etc/di.xml
        $this->reader = $reader;
    }

    /**
     * @inheriDoc
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = [])
    {
        if (!$this->isCacheEnabled) {
            return parent::clean($mode, $tags);
        }

        $result = parent::clean($mode, $tags);
        $this->refreshGraphQlSchema();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function remove($identifier)
    {
        if (!$this->isCacheEnabled) {
            return parent::remove($identifier);
        }

        // Might be redundant, but just to be sure

        $result = parent::remove($identifier);
        $this->refreshGraphQlSchema();
        return $result;
    }

    /**
     * Refresh the graphql schema cache
     *
     * @return void
     */
    public function refreshGraphQlSchema(): void
    {
        // @TODO: Determine if we need to iterate all scopes;
        //          i.e. \Magento\Framework\App\Area:: constants
        $this->reader->read();
    }
}
