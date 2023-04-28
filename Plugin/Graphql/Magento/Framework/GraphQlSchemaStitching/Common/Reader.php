<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Plugin\Graphql\Magento\Framework\GraphQlSchemaStitching\Common;

use HappyHorizon\PersistentGraphQlSchema\Model\Cache\GraphQlSchemaCache;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Reader
{
    private GraphQlSchemaCache $cache;
    private bool $isCacheEnabled;
    private SerializerInterface $serializer;

    /**
     * @param GraphQlSchemaCache $cache
     * @param StateInterface $cacheState
     * @param SerializerInterface $serializer
     */
    public function __construct(
        GraphQlSchemaCache $cache,
        StateInterface $cacheState,
        SerializerInterface $serializer
    ) {
        $this->cache = $cache;
        $this->isCacheEnabled = $cacheState->isEnabled(GraphQlSchemaCache::TYPE_IDENTIFIER);
        $this->serializer = $serializer;
    }

    /**
     * @param \Magento\Framework\GraphQlSchemaStitching\Common\Reader $subject
     * @param \Closure $proceed
     * @param $scope
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundRead(
        \Magento\Framework\GraphQlSchemaStitching\Common\Reader $subject,
        \Closure $proceed,
        $scope = null
    ): array {
        $cacheId = GraphQlSchemaCache::TYPE_IDENTIFIER;
        if ($scope) {
            $cacheId .= '_' . $scope;
        }

        if ($this->isCacheEnabled && ($graphQlSchema = $this->cache->load($cacheId))) {
            return $this->serializer->unserialize($graphQlSchema);
        }
        $graphQlSchema = $proceed();
        if ($this->isCacheEnabled) {
            $this->cache->save(
                $this->serializer->serialize($graphQlSchema),
                $cacheId,
                [GraphQlSchemaCache::CACHE_TAG]
            );
        }
        return $graphQlSchema;
    }
}
