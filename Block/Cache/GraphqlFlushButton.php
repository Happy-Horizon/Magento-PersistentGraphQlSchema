<?php
/**
 * Copyright © Happy Horizon Utrecht Development & Technology B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HappyHorizon\PersistentGraphQlSchema\Block\Cache;

class GraphqlFlushButton extends \Magento\Backend\Block\Template
{
    /**
     * @return string
     */
    public function getFlushUrl(): string
    {
        return $this->getUrl('happyhorizon_persistentgraphql/cache/cleanGraphql');
    }
}
