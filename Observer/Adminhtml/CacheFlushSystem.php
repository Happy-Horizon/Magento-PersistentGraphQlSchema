<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Observer\Adminhtml;

use HappyHorizon\PersistentGraphQlSchema\Model\Cache\GraphQlSchemaCache;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CacheFlushSystem implements ObserverInterface
{
    private GraphQlSchemaCache $graphQlCache;

    /**
     * @param GraphQlSchemaCache $graphQlCache
     */
    public function __construct(
        GraphQlSchemaCache $graphQlCache
    ) {
        $this->graphQlCache = $graphQlCache;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(
        Observer $observer
    ) {
        $this->graphQlCache->refreshGraphQlSchema();
    }
}
