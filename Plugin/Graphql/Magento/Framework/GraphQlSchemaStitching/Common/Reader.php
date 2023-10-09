<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Plugin\Graphql\Magento\Framework\GraphQlSchemaStitching\Common;

use HappyHorizon\PersistentGraphQlSchema\Model\Cache\GraphQlSchemaCache;
use Magento\Framework\Serialize\SerializerInterface;

class Reader
{
    /**
     * @param GraphQlSchemaCache $graphQlSchemaCache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private GraphQlSchemaCache $graphQlSchemaCache,
        private SerializerInterface $serializer
    ) {
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

        if ($this->graphQlSchemaCache->isCacheEnabled === true
            && ($graphQlSchema = $this->graphQlSchemaCache->load($cacheId))
        ) {
            return $this->serializer->unserialize($graphQlSchema);
        }
        $graphQlSchema = $proceed();
        if ($this->graphQlSchemaCache->isCacheEnabled === true) {
            $this->graphQlSchemaCache->save(
                $this->serializer->serialize($graphQlSchema),
                $cacheId,
                [GraphQlSchemaCache::CACHE_TAG]
            );
        }
        return $graphQlSchema;
    }
}
