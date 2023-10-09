<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Plugin\Magento\Framework\App\Cache;

use HappyHorizon\PersistentGraphQlSchema\Model\Cache\GraphQlSchemaCache;

class Manager
{
    /**
     * @param GraphQlSchemaCache $graphQlSchemaCache
     */
    public function __construct(
        protected GraphQlSchemaCache $graphQlSchemaCache
    ) {
    }

    /**
     * @param \Magento\Framework\App\Cache\Manager $subject
     * @param $result
     * @param string[] $types
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterFlush(
        \Magento\Framework\App\Cache\Manager $subject,
        $result,
        array $types
    ) {
        if ($this->graphQlSchemaCache->isCacheEnabled === true
            && in_array(GraphQlSchemaCache::TYPE_IDENTIFIER, $types)
        ) {
            $this->graphQlSchemaCache->refreshGraphQlSchema();
        }

        return $result;
    }
}
